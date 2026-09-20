<?php

namespace App\Http\Controllers;

use App\Http\Requests\HistoricoFamiliarRequest;
use App\Models\Beneficiario;
use App\Models\Historico_familiar;
use App\Services\PrioridadeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HistoricoFamiliarController extends Controller
{
    public function create(Beneficiario $beneficiario)
    {
        return view('HistoricoFamiliar.CadastrarHistoricoFamiliar', compact('beneficiario'));
    }

    public function store(
        HistoricoFamiliarRequest $request,
        Beneficiario $beneficiario,
        PrioridadeService $prioridadeService
    ) {
        $dados = $this->prepararDados($request);

        DB::transaction(function () use ($beneficiario, $dados, $prioridadeService): void {
            $beneficiarioBloqueado = Beneficiario::query()
                ->lockForUpdate()
                ->findOrFail($beneficiario->getKey());
            $beneficiarioBloqueado->historicoFamiliar()->create($dados);
            $this->recalcularAgendamentosAbertos($beneficiarioBloqueado, $prioridadeService);
        });

        return redirect()
            ->route('beneficiarios.show', $beneficiario)
            ->with('success', 'Histórico familiar cadastrado com sucesso.');
    }

    public function edit(Beneficiario $beneficiario, Historico_familiar $historicoFamiliar)
    {
        $this->verificarVinculo($beneficiario, $historicoFamiliar);

        return view('HistoricoFamiliar.EditarHistoricoFamiliar', compact('beneficiario', 'historicoFamiliar'));
    }

    public function update(
        HistoricoFamiliarRequest $request,
        Beneficiario $beneficiario,
        Historico_familiar $historicoFamiliar,
        PrioridadeService $prioridadeService
    ) {
        $this->verificarVinculo($beneficiario, $historicoFamiliar);
        $dados = $this->prepararDados($request);

        DB::transaction(function () use (
            $beneficiario,
            $historicoFamiliar,
            $dados,
            $prioridadeService
        ): void {
            $beneficiarioBloqueado = Beneficiario::query()
                ->lockForUpdate()
                ->findOrFail($beneficiario->getKey());
            $historicoBloqueado = Historico_familiar::query()
                ->lockForUpdate()
                ->findOrFail($historicoFamiliar->getKey());
            $this->verificarVinculo($beneficiarioBloqueado, $historicoBloqueado);
            $historicoBloqueado->update($dados);
            $this->recalcularAgendamentosAbertos($beneficiarioBloqueado, $prioridadeService);
        });

        return redirect()
            ->route('beneficiarios.show', $beneficiario)
            ->with('success', 'Histórico familiar atualizado com sucesso.');
    }

    public function destroy(
        Beneficiario $beneficiario,
        Historico_familiar $historicoFamiliar,
        PrioridadeService $prioridadeService
    ) {
        $this->verificarVinculo($beneficiario, $historicoFamiliar);

        DB::transaction(function () use (
            $beneficiario,
            $historicoFamiliar,
            $prioridadeService
        ): void {
            $beneficiarioBloqueado = Beneficiario::query()
                ->lockForUpdate()
                ->findOrFail($beneficiario->getKey());
            $historicoBloqueado = Historico_familiar::query()
                ->lockForUpdate()
                ->findOrFail($historicoFamiliar->getKey());
            $this->verificarVinculo($beneficiarioBloqueado, $historicoBloqueado);
            $historicoBloqueado->delete();
            $this->recalcularAgendamentosAbertos($beneficiarioBloqueado, $prioridadeService);
        });

        return redirect()
            ->route('beneficiarios.show', $beneficiario)
            ->with('success', 'Registro do histórico familiar excluído com sucesso.');
    }

    private function prepararDados(HistoricoFamiliarRequest $request): array
    {
        $dados = $request->validated();
        $dados['data_nascimento'] = Carbon::createFromFormat(
            'd/m/Y',
            $dados['data_nascimento']
        )->format('Y-m-d');
        $dados['possui_problema_saude'] = $request->boolean('possui_problema_saude');
        $dados['possui_deficiencia'] = $request->boolean('possui_deficiencia');
        $dados['descricao_problema_saude'] = $dados['possui_problema_saude']
            ? ($dados['descricao_problema_saude'] ?? null)
            : null;
        $dados['descricao_deficiencia'] = $dados['possui_deficiencia']
            ? ($dados['descricao_deficiencia'] ?? null)
            : null;

        return $dados;
    }

    private function verificarVinculo(
        Beneficiario $beneficiario,
        Historico_familiar $historicoFamiliar
    ): void {
        if ((int) $historicoFamiliar->beneficiario_id !== (int) $beneficiario->getKey()) {
            abort(404);
        }
    }

    private function recalcularAgendamentosAbertos(
        Beneficiario $beneficiario,
        PrioridadeService $prioridadeService
    ): void {
        $beneficiario->unsetRelation('historicoFamiliar');
        $beneficiario->load('historicoFamiliar');

        $prioridadeCalculada = $prioridadeService->calcular($beneficiario);

        $beneficiario->agendamentos()
            ->whereIn('status', ['agendado', 'reagendado'])
            ->update([
                'prioridade' => $prioridadeCalculada['nivel'],
                'pontuacao_prioridade' => $prioridadeCalculada['pontos'],
                'justificativa_prioridade' => $prioridadeCalculada['justificativa'],
                'lock_version' => DB::raw('lock_version + 1'),
            ]);
    }
}
