<?php

namespace App\Http\Controllers;

use App\Http\Requests\HistoricoAcoesRequest;
use App\Models\Agendamento;
use App\Models\Historico_acoes;
use App\Services\MaquinaEstadosAgendamento;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HistoricoAcoesController extends Controller
{
    public function create(
        Request $request,
        Agendamento $agendamento,
        MaquinaEstadosAgendamento $maquinaEstados
    ) {
        $this->validarAcesso($request, $agendamento);
        $this->validarConclusaoPermitida($agendamento, $maquinaEstados);
        $this->validarHorarioOcorrido($agendamento);

        $agendamento->load('beneficiario');

        return view('HistoricoAcoes.CriaHistoricoAcoes', compact('agendamento'));
    }

    public function store(
        HistoricoAcoesRequest $request,
        Agendamento $agendamento,
        MaquinaEstadosAgendamento $maquinaEstados
    ) {
        $dados = $request->validated();
        $dados['data_atendimento'] = Carbon::createFromFormat(
            'd/m/Y',
            $dados['data_atendimento']
        )->format('Y-m-d');

        try {
            DB::transaction(function () use (
                $request,
                $agendamento,
                $dados,
                $maquinaEstados
            ): void {
                $agendamentoBloqueado = Agendamento::query()
                    ->lockForUpdate()
                    ->findOrFail($agendamento->getKey());

                $this->validarAcesso($request, $agendamentoBloqueado);
                $this->validarConclusaoPermitida($agendamentoBloqueado, $maquinaEstados);
                $this->validarHorarioOcorrido($agendamentoBloqueado);

                if ($agendamentoBloqueado->historicoAcoes()->exists()) {
                    throw ValidationException::withMessages([
                        'agendamento' => 'Este agendamento já possui um relatório de atendimento.',
                    ]);
                }

                $dataAtendimento = Carbon::createFromFormat('Y-m-d', $dados['data_atendimento']);

                if ($dataAtendimento->startOfDay()->lt($agendamentoBloqueado->data_agendada->startOfDay())) {
                    throw ValidationException::withMessages([
                        'data_atendimento' => 'A data realizada não pode ser anterior à data agendada.',
                    ]);
                }

                $historico = new Historico_acoes([
                    'descricao' => $dados['descricao'],
                    'tipo_atendimento' => $dados['tipo_atendimento'] ?? null,
                    'data_atendimento' => $dados['data_atendimento'],
                    'encaminhamentos' => $dados['encaminhamentos'] ?? null,
                    'observacoes' => $dados['observacoes'] ?? null,
                ]);
                $historico->beneficiario()->associate($agendamentoBloqueado->beneficiario_id);
                $historico->agendamento()->associate($agendamentoBloqueado);
                $historico->usuario()->associate($request->user());
                $historico->save();

                $transicionou = $maquinaEstados->transicionarCondicional(
                    $agendamentoBloqueado,
                    MaquinaEstadosAgendamento::COMPLETADO
                );

                if (! $transicionou) {
                    throw ValidationException::withMessages([
                        'agendamento' => 'O estado deste agendamento mudou durante o registro do relatório. Atualize a página.',
                    ]);
                }
            });
        } catch (QueryException $exception) {
            if (! $this->ehViolacaoDeRelatorioUnico($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'agendamento' => 'Este agendamento já possui um relatório de atendimento.',
            ]);
        }

        return redirect()
            ->route('agendamento.show', $agendamento)
            ->with('success', 'Relatório de atendimento registrado com sucesso.');
    }

    private function validarAcesso(Request $request, Agendamento $agendamento): void
    {
        $usuario = $request->user();

        if (
            $usuario->role === 'voluntario'
            && (int) $agendamento->responsavel_id !== (int) $usuario->getKey()
        ) {
            abort(403, 'Você não possui permissão para registrar o relatório deste atendimento.');
        }

    }

    private function validarConclusaoPermitida(
        Agendamento $agendamento,
        MaquinaEstadosAgendamento $maquinaEstados
    ): void {
        if (! $maquinaEstados->podeTransicionar(
            $agendamento->status,
            MaquinaEstadosAgendamento::COMPLETADO
        )) {
            abort(403, 'Este agendamento não permite o registro de um novo relatório.');
        }
    }

    private function validarHorarioOcorrido(Agendamento $agendamento): void
    {
        $instanteAgendado = Carbon::createFromFormat(
            'Y-m-d H:i',
            $agendamento->data_agendada->format('Y-m-d').' '.substr($agendamento->hora_agendada, 0, 5),
            config('app.timezone')
        );

        if ($instanteAgendado->isFuture()) {
            abort(403, 'O relatório só pode ser registrado após o início previsto do atendimento.');
        }
    }

    private function ehViolacaoDeRelatorioUnico(QueryException $exception): bool
    {
        $mensagem = strtolower($exception->getMessage());

        return str_contains($mensagem, 'historico_acoes_agendamento_unique')
            || str_contains(
                $mensagem,
                'unique constraint failed: historico_acoes.agendamento_id'
            );
    }
}
