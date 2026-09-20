<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgendamentoIndexRequest;
use App\Http\Requests\AgendamentoRequest;
use App\Http\Requests\CancelarAgendamentoRequest;
use App\Http\Requests\ReabrirAgendamentoRequest;
use App\Http\Requests\TransicaoStatusAgendamentoRequest;
use App\Models\Agendamento;
use App\Models\Beneficiario;
use App\Models\User;
use App\Services\MaquinaEstadosAgendamento;
use App\Services\PrioridadeService;
use App\Support\Cpf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;

class AgendamentoController extends Controller
{
    public function index(AgendamentoIndexRequest $request)
{
    $usuario = $request->user();
    $filtros = $request->validated();

    [$dataInicio, $dataFim] =
        $this->converterDatasDosFiltros($filtros);

    $agendamentos = $this->consultaAgenda(
        $usuario,
        $filtros,
        $dataInicio,
        $dataFim
    )
        ->whereIn(
            'status',
            MaquinaEstadosAgendamento::STATUS_ABERTOS
        )
        ->where('data_agendada', '>=', today()->toDateString())
        ->orderBy('data_agendada')
        ->orderBy('hora_agendada')
        ->paginate(10)
        ->withQueryString();

    $totalPendenciasVencidas = Agendamento::query()
        ->when(
            $usuario->role === 'voluntario',
            function (Builder $query) use ($usuario) {
                $query->where(
                    'responsavel_id',
                    $usuario->getKey()
                );
            }
        )
        ->whereIn(
            'status',
            MaquinaEstadosAgendamento::STATUS_ABERTOS
        )
        ->where('data_agendada', '<', today()->toDateString())
        ->count();

    return view(
        'Agendamentos.agendamentos',
        compact(
            'agendamentos',
            'totalPendenciasVencidas'
        )
    );
}

    public function create(Request $request)
    {
        $beneficiarios = Beneficiario::ativos()
            ->orderBy('nome_beneficiario')
            ->get(['id', 'nome_beneficiario', 'cpf', 'is_active']);

        $responsaveis = User::whereIn('role', ['lider', 'voluntario'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'is_active']);

        $beneficiarioSelecionado = $request->beneficiario_id;

        return view('Agendamentos.CriarNovoAgendamento', compact(
            'beneficiarios',
            'responsaveis',
            'beneficiarioSelecionado'
        ));
    }

    public function historico(AgendamentoIndexRequest $request)
{
    $usuario = $request->user();
    $filtros = $request->validated();

    [$dataInicio, $dataFim] =
        $this->converterDatasDosFiltros($filtros);

    $pendencias = $this->consultaAgenda(
        $usuario,
        $filtros,
        $dataInicio,
        $dataFim
    )
        ->whereIn(
            'status',
            MaquinaEstadosAgendamento::STATUS_ABERTOS
        )
        ->where('data_agendada', '<', today()->toDateString())
        ->orderBy('data_agendada')
        ->orderBy('hora_agendada')
        ->paginate(
            10,
            ['*'],
            'pendencias_page'
        )
        ->withQueryString();

    $agendamentosEncerrados = $this->consultaAgenda(
        $usuario,
        $filtros,
        $dataInicio,
        $dataFim
    )
        ->whereIn(
            'status',
            MaquinaEstadosAgendamento::STATUS_ENCERRADOS
        )
        ->orderByDesc('data_agendada')
        ->orderByDesc('hora_agendada')
        ->paginate(
            10,
            ['*'],
            'historico_page'
        )
        ->withQueryString();

    return view(
        'Agendamentos.historico',
        compact(
            'pendencias',
            'agendamentosEncerrados'
        )
    );
}

    public function store(AgendamentoRequest $request, PrioridadeService $prioridadeService)
    {
        $dados = $request->validated();

        if (! $this->instanteAgendado($dados)->isFuture()) {
            throw ValidationException::withMessages([
                'hora_agendada' => 'Informe uma data e um horário futuros para o novo agendamento.',
            ]);
        }

        $dados['data_agendada'] = Carbon::createFromFormat('d/m/Y', $dados['data_agendada'])
            ->format('Y-m-d');
        $dados['status'] = MaquinaEstadosAgendamento::AGENDADO;

        DB::transaction(function () use ($request, $dados, $prioridadeService): void {
            $this->bloquearDatasAgenda([$dados['data_agendada']]);
            $this->validarDisponibilidadeAgendamento($dados);

            $beneficiario = $this->bloquearEValidarVinculos($dados);
            $prioridadeCalculada = $prioridadeService->calcular($beneficiario);

            $agendamento = new Agendamento($dados);
            $agendamento->criador()->associate($request->user());
            $agendamento->status = MaquinaEstadosAgendamento::AGENDADO;
            $agendamento->prioridade = $prioridadeCalculada['nivel'];
            $agendamento->pontuacao_prioridade = $prioridadeCalculada['pontos'];
            $agendamento->justificativa_prioridade = $prioridadeCalculada['justificativa'];
            $agendamento->save();
        });

        return redirect()
            ->route('agendamento.index')
            ->with('success', 'Agendamento cadastrado com prioridade calculada automaticamente.');
    }

    public function show(Request $request, Agendamento $agendamento)
    {
        $usuario = $request->user();

        if (
            $usuario->role === 'voluntario'
            && (int) $agendamento->responsavel_id !== (int) $usuario->getKey()
        ) {
            abort(403, 'Você não possui permissão para acessar este atendimento.');
        }

        $agendamento->load([
            'beneficiario',
            'criador',
            'responsavel',
            'historicoAcoes.usuario',
        ]);
        $horarioAgendadoJaPassou = $agendamento->horarioAgendadoJaPassou();
        $voltarParaHistorico = in_array(
    $agendamento->status,
    MaquinaEstadosAgendamento::STATUS_ENCERRADOS,
    true
) || (
    in_array(
        $agendamento->status,
        MaquinaEstadosAgendamento::STATUS_ABERTOS,
        true
    )
    && $agendamento->data_agendada->lt(today())
);

$urlVoltar = $voltarParaHistorico
    ? route('agendamento.historico')
    : route('agendamento.index');

$textoVoltar = $voltarParaHistorico
    ? 'Voltar ao histórico'
    : 'Voltar à agenda';

        return view(
            'Agendamentos.Mostrar',
            compact('agendamento', 'horarioAgendadoJaPassou','urlVoltar','textoVoltar',)
        );
    }

    public function edit(Agendamento $agendamento)
    {
        if (! in_array($agendamento->status, MaquinaEstadosAgendamento::STATUS_ABERTOS, true)) {
            abort(403, 'Somente agendamentos abertos podem ser editados.');
        }

        $beneficiarios = Beneficiario::ativos()
            ->orWhere('id', $agendamento->beneficiario_id)
            ->orderBy('nome_beneficiario')
            ->get(['id', 'nome_beneficiario', 'cpf', 'is_active']);

        $responsaveis = User::whereIn('role', ['lider', 'voluntario'])
            ->where(function ($query) use ($agendamento) {
                $query->where('is_active', true);

                if ($agendamento->responsavel_id) {
                    $query->orWhere(
                        'id',
                        $agendamento->responsavel_id
                    );
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'is_active']);

        return view(
            'Agendamentos.EditarAgendamento',
            compact(
                'agendamento',
                'beneficiarios',
                'responsaveis'
            )
        );
    }

    public function update(
        AgendamentoRequest $request,
        Agendamento $agendamento,
        PrioridadeService $prioridadeService,
        MaquinaEstadosAgendamento $maquinaEstados
    ) {
        $dados = $request->validated();
        $versaoEsperada = (int) $dados['lock_version'];
        unset($dados['lock_version']);

        $instanteSolicitado = $this->instanteAgendado($dados);

        $dados['data_agendada'] = Carbon::createFromFormat('d/m/Y', $dados['data_agendada'])
            ->format('Y-m-d');

        if (! $instanteSolicitado->isFuture()) {
            throw ValidationException::withMessages([
                'hora_agendada' => 'Para manter ou reagendar o atendimento, informe uma data e um horário futuros.',
            ]);
        }

        DB::transaction(function () use (
            $agendamento,
            $dados,
            $versaoEsperada,
            $prioridadeService,
            $maquinaEstados
        ): void {
            $this->bloquearDatasAgenda([
                $agendamento->data_agendada->format('Y-m-d'),
                $dados['data_agendada'],
            ]);

            $beneficiario = $this->bloquearEValidarVinculos($dados, $agendamento);

            $agendamentoBloqueado = Agendamento::query()
                ->lockForUpdate()
                ->findOrFail($agendamento->getKey());

            $this->validarVersao($agendamentoBloqueado, $versaoEsperada);

            if (! in_array($agendamentoBloqueado->status, MaquinaEstadosAgendamento::STATUS_ABERTOS, true)) {
                abort(403, 'Agendamentos encerrados não podem ser alterados por este formulário.');
            }

            $this->validarDisponibilidadeAgendamento($dados, $agendamentoBloqueado);

            $prioridadeCalculada = $prioridadeService->calcular($beneficiario);
            $foiReagendado = $agendamentoBloqueado->status === MaquinaEstadosAgendamento::AGENDADO
                && $this->horarioFoiAlterado($dados, $agendamentoBloqueado);

            $agendamentoBloqueado->fill($dados);
            $agendamentoBloqueado->prioridade = $prioridadeCalculada['nivel'];
            $agendamentoBloqueado->pontuacao_prioridade = $prioridadeCalculada['pontos'];
            $agendamentoBloqueado->justificativa_prioridade = $prioridadeCalculada['justificativa'];

            if ($foiReagendado) {
                $maquinaEstados->transicionar(
                    $agendamentoBloqueado,
                    MaquinaEstadosAgendamento::REAGENDADO
                );
            } else {
                $agendamentoBloqueado->lock_version = (int) $agendamentoBloqueado->lock_version + 1;
            }

            $agendamentoBloqueado->save();
        });

        return redirect()
            ->route('agendamento.index')
            ->with('success', 'Agendamento atualizado com prioridade recalculada automaticamente.');
    }

    public function cancelar(
        CancelarAgendamentoRequest $request,
        Agendamento $agendamento,
        MaquinaEstadosAgendamento $maquinaEstados
    ) {
        $dados = $request->validated();
        $versaoEsperada = (int) $dados['lock_version'];
        $motivo = isset($dados['motivo_cancelamento'])
            ? trim($dados['motivo_cancelamento'])
            : null;
        $motivo = $motivo !== '' ? $motivo : null;

        DB::transaction(function () use (
            $agendamento,
            $versaoEsperada,
            $motivo,
            $maquinaEstados
        ): void {
            $agendamentoBloqueado = Agendamento::query()
                ->lockForUpdate()
                ->findOrFail($agendamento->getKey());

            $this->validarVersao($agendamentoBloqueado, $versaoEsperada);

            if (! $maquinaEstados->podeTransicionar(
                $agendamentoBloqueado->status,
                MaquinaEstadosAgendamento::CANCELADO
            )) {
                abort(403, 'Este agendamento não pode mais ser cancelado.');
            }

            $transicionou = $maquinaEstados->transicionarCondicional(
                $agendamentoBloqueado,
                MaquinaEstadosAgendamento::CANCELADO,
                ['motivo_cancelamento' => $motivo]
            );

            if (! $transicionou) {
                $this->falharPorConcorrencia();
            }
        });

        return redirect()
    ->route('agendamento.historico')
    ->with(
        'success',
        'Agendamento cancelado com sucesso.'
    );
    }

    public function marcarComoPerdido(
        TransicaoStatusAgendamentoRequest $request,
        Agendamento $agendamento,
        MaquinaEstadosAgendamento $maquinaEstados
    ) {
        $versaoEsperada = (int) $request->validated('lock_version');

        DB::transaction(function () use (
            $agendamento,
            $versaoEsperada,
            $maquinaEstados
        ): void {
            $agendamentoBloqueado = Agendamento::query()
                ->lockForUpdate()
                ->findOrFail($agendamento->getKey());

            $this->validarVersao($agendamentoBloqueado, $versaoEsperada);

            if (! $maquinaEstados->podeTransicionar(
                $agendamentoBloqueado->status,
                MaquinaEstadosAgendamento::PERDIDO
            )) {
                abort(403, 'Somente um agendamento aberto pode ser marcado como perdido.');
            }

            if ($this->instantePersistido($agendamentoBloqueado)->isFuture()) {
                throw ValidationException::withMessages([
                    'status' => 'Somente um atendimento cujo horário agendado já passou pode ser marcado como perdido.',
                ]);
            }

            $transicionou = $maquinaEstados->transicionarCondicional(
                $agendamentoBloqueado,
                MaquinaEstadosAgendamento::PERDIDO
            );

            if (! $transicionou) {
                $this->falharPorConcorrencia();
            }
        });

        return redirect()
    ->route('agendamento.historico')
    ->with(
        'success',
        'Agendamento marcado como perdido.'
    );
    }

    public function reabrir(
        ReabrirAgendamentoRequest $request,
        Agendamento $agendamento,
        MaquinaEstadosAgendamento $maquinaEstados
    ) {
        $dados = $request->validated();
        $versaoEsperada = (int) $dados['lock_version'];
        unset($dados['lock_version']);

        if (! $this->instanteAgendado($dados)->isFuture()) {
            throw ValidationException::withMessages([
                'hora_agendada' => 'Para reabrir o atendimento, informe uma data e um horário futuros.',
            ]);
        }

        $dados['data_agendada'] = Carbon::createFromFormat('d/m/Y', $dados['data_agendada'])
            ->format('Y-m-d');

        DB::transaction(function () use (
            $agendamento,
            $dados,
            $versaoEsperada,
            $maquinaEstados
        ): void {
            $this->bloquearDatasAgenda([
                $agendamento->data_agendada->format('Y-m-d'),
                $dados['data_agendada'],
            ]);

            $agendamentoBloqueado = Agendamento::query()
                ->lockForUpdate()
                ->findOrFail($agendamento->getKey());

            $this->validarVersao($agendamentoBloqueado, $versaoEsperada);

            if (! in_array(
                $agendamentoBloqueado->status,
                MaquinaEstadosAgendamento::STATUS_REABRIVEIS,
                true
            )) {
                abort(403, 'Somente agendamentos cancelados ou perdidos podem ser reabertos.');
            }

            $dadosDisponibilidade = [
                'beneficiario_id' => $agendamentoBloqueado->beneficiario_id,
                'responsavel_id' => $agendamentoBloqueado->responsavel_id,
                'data_agendada' => $dados['data_agendada'],
                'hora_agendada' => $dados['hora_agendada'],
                'hora_final_agendada' => $dados['hora_final_agendada'] ?? null,
                'local' => $agendamentoBloqueado->local,
            ];

            $this->validarDisponibilidadeAgendamento(
                $dadosDisponibilidade,
                $agendamentoBloqueado
            );

            $transicionou = $maquinaEstados->transicionarCondicional(
                $agendamentoBloqueado,
                MaquinaEstadosAgendamento::REAGENDADO,
                [
                    'data_agendada' => $dados['data_agendada'],
                    'hora_agendada' => $dados['hora_agendada'],
                    'hora_final_agendada' => $dados['hora_final_agendada'] ?? null,
                ]
            );

            if (! $transicionou) {
                $this->falharPorConcorrencia();
            }
        });

        return redirect()
            ->route('agendamento.show', $agendamento)
            ->with('success', 'Agendamento reaberto e reagendado com sucesso.');
    }

    private function instanteAgendado(array $dados): Carbon
    {
        return Carbon::createFromFormat(
            'd/m/Y H:i',
            $dados['data_agendada'].' '.$dados['hora_agendada'],
            config('app.timezone')
        );
    }

    private function converterDatasDosFiltros(
    array $filtros
): array {
    $dataInicio = ! empty($filtros['data_inicio'])
        ? Carbon::createFromFormat(
            'd/m/Y',
            $filtros['data_inicio']
        )->format('Y-m-d')
        : null;

    $dataFim = ! empty($filtros['data_fim'])
        ? Carbon::createFromFormat(
            'd/m/Y',
            $filtros['data_fim']
        )->format('Y-m-d')
        : null;

    if (
        $dataInicio
        && $dataFim
        && $dataInicio > $dataFim
    ) {
        throw ValidationException::withMessages([
            'data_inicio' =>
                'A data inicial não pode ser posterior à data final.',
        ]);
    }

    return [$dataInicio, $dataFim];
}

private function consultaAgenda(
    User $usuario,
    array $filtros,
    ?string $dataInicio,
    ?string $dataFim
): Builder {
    return Agendamento::query()
        ->with([
            'beneficiario:id,nome_beneficiario',
            'responsavel:id,name',
        ])
        ->when(
            $usuario->role === 'voluntario',
            function (Builder $query) use ($usuario) {
                $query->where(
                    'responsavel_id',
                    $usuario->getKey()
                );
            }
        )
        ->when(
            ! empty($filtros['search']),
            function (Builder $query) use ($filtros) {
                $search = $filtros['search'];
                $cpfSearch = Cpf::normalize($search);

                $query->whereHas(
                    'beneficiario',
                    function (
                        Builder $queryBeneficiario
                    ) use (
                        $search,
                        $cpfSearch
                    ) {
                        $queryBeneficiario->where(
                            'nome_beneficiario',
                            'like',
                            "%{$search}%"
                        );

                        if ($cpfSearch !== '') {
                            $queryBeneficiario->orWhere(
                                'cpf',
                                'like',
                                "%{$cpfSearch}%"
                            );
                        }
                    }
                );
            }
        )
        ->when(
            $dataInicio,
            function (Builder $query) use ($dataInicio) {
                $query->where(
                    'data_agendada',
                    '>=',
                    $dataInicio
                );
            }
        )
        ->when(
            $dataFim,
            function (Builder $query) use ($dataFim) {
                $query->where(
                    'data_agendada',
                    '<',
                    Carbon::parse($dataFim)->addDay()->toDateString()
                );
            }
        )
        ->when(
            ! empty($filtros['status']),
            function (Builder $query) use ($filtros) {
                $query->where(
                    'status',
                    $filtros['status']
                );
            }
        )
        ->when(
            ! empty($filtros['prioridade']),
            function (Builder $query) use ($filtros) {
                $query->where(
                    'prioridade',
                    $filtros['prioridade']
                );
            }
        );
}

    private function instantePersistido(Agendamento $agendamento): Carbon
    {
        return Carbon::createFromFormat(
            'Y-m-d H:i',
            $agendamento->data_agendada->format('Y-m-d').' '.substr($agendamento->hora_agendada, 0, 5),
            config('app.timezone')
        );
    }

    private function validarVersao(Agendamento $agendamento, int $versaoEsperada): void
    {
        if ((int) $agendamento->lock_version !== $versaoEsperada) {
            $this->falharPorConcorrencia();
        }
    }

    private function falharPorConcorrencia(): never
    {
        throw ValidationException::withMessages([
            'agendamento' => 'Este agendamento foi alterado por outra operação. Atualize a página antes de continuar.',
        ]);
    }

    private function horarioFoiAlterado(array $dados, Agendamento $agendamento): bool
    {
        return $dados['data_agendada'] !== $agendamento->data_agendada->format('Y-m-d')
            || $dados['hora_agendada'] !== substr($agendamento->hora_agendada, 0, 5)
            || ($dados['hora_final_agendada'] ?? null) !== (
                $agendamento->hora_final_agendada
                    ? substr($agendamento->hora_final_agendada, 0, 5)
                    : null
            );
    }

    private function bloquearEValidarVinculos(
        array $dados,
        ?Agendamento $agendamentoAtual = null
    ): Beneficiario {
        $beneficiario = Beneficiario::query()
            ->lockForUpdate()
            ->findOrFail($dados['beneficiario_id']);

        if (
            ! $beneficiario->is_active
            && (int) $agendamentoAtual?->beneficiario_id !== (int) $beneficiario->getKey()
        ) {
            throw ValidationException::withMessages([
                'beneficiario_id' => 'Selecione um beneficiário ativo ou mantenha o vínculo atual.',
            ]);
        }

        if (! empty($dados['responsavel_id'])) {
            $responsavel = User::query()
                ->lockForUpdate()
                ->findOrFail($dados['responsavel_id']);
            $ehResponsavelAtual = (int) $agendamentoAtual?->responsavel_id === (int) $responsavel->getKey();

            if (
                ! in_array($responsavel->role, ['lider', 'voluntario'], true)
                || (! $responsavel->is_active && ! $ehResponsavelAtual)
            ) {
                throw ValidationException::withMessages([
                    'responsavel_id' => 'Selecione um responsável ativo com perfil de líder ou voluntário, ou mantenha o vínculo atual.',
                ]);
            }
        }

        return $beneficiario->load('historicoFamiliar');
    }

    private function validarDisponibilidadeAgendamento(
        array $dados,
        ?Agendamento $agendamentoAtual = null
    ): void {
        $local = trim((string) ($dados['local'] ?? ''));

        $conflitos = Agendamento::query()
            ->where('data_agendada', '>=', $dados['data_agendada'])
            ->where('data_agendada', '<', Carbon::parse($dados['data_agendada'])->addDay()->toDateString())
            ->whereIn('status', MaquinaEstadosAgendamento::STATUS_ABERTOS)
            ->when($agendamentoAtual, function ($query) use ($agendamentoAtual) {
                $query->whereKeyNot($agendamentoAtual->getKey());
            })
            ->where(function ($query) use ($dados, $local) {
                $query->where('beneficiario_id', $dados['beneficiario_id']);

                if (! empty($dados['responsavel_id'])) {
                    $query->orWhere('responsavel_id', $dados['responsavel_id']);
                }

                if ($local !== '') {
                    $query->orWhereRaw('LOWER(local) = ?', [strtolower($local)]);
                }
            })
            ->lockForUpdate()
            ->get();

        if ($conflitos->isEmpty()) {
            return;
        }

        $inicio = $this->horaParaMinutos($dados['hora_agendada']);
        $fim = $this->horaParaMinutos($dados['hora_final_agendada'] ?? null, $inicio);
        $erros = [];

        foreach ($conflitos as $conflito) {
            $inicioConflito = $this->horaParaMinutos($conflito->hora_agendada);
            $fimConflito = $this->horaParaMinutos($conflito->hora_final_agendada, $inicioConflito);

            if (! $this->horariosSobrepostos($inicio, $fim, $inicioConflito, $fimConflito)) {
                continue;
            }

            if ((int) $conflito->beneficiario_id === (int) $dados['beneficiario_id']) {
                $erros['beneficiario_id'] ??= [];
                $erros['beneficiario_id'][] = 'Este beneficiário já possui agendamento aberto nesse horário.';
            }

            if (
                ! empty($dados['responsavel_id'])
                && (int) $conflito->responsavel_id === (int) $dados['responsavel_id']
            ) {
                $erros['responsavel_id'] ??= [];
                $erros['responsavel_id'][] = 'Este responsável já possui agendamento aberto nesse horário.';
            }

            if (
                $local !== ''
                && strtolower((string) $conflito->local) === strtolower($local)
            ) {
                $erros['local'] ??= [];
                $erros['local'][] = 'Este local já possui agendamento aberto nesse horário.';
            }
        }

        if (! empty($erros)) {
            throw ValidationException::withMessages($erros);
        }
    }

    private function bloquearDatasAgenda(array $datas): void
    {
        $datas = array_values(array_unique($datas));
        sort($datas);

        foreach ($datas as $data) {
            DB::table('agendamento_data_locks')->insertOrIgnore([
                'data_agendada' => $data,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('agendamento_data_locks')
                ->where('data_agendada', $data)
                ->lockForUpdate()
                ->first();
        }
    }

    private function horariosSobrepostos(
        int $inicio,
        int $fim,
        int $inicioComparado,
        int $fimComparado
    ): bool {
        return $inicio < $fimComparado && $fim > $inicioComparado;
    }

    private function horaParaMinutos(?string $hora, ?int $inicio = null): int
    {
        if (! $hora) {
            return min(($inicio ?? 0) + 1, 1440);
        }

        [$horas, $minutos] = array_map('intval', explode(':', substr($hora, 0, 5)));
        $total = ($horas * 60) + $minutos;

        if ($inicio !== null && $total <= $inicio) {
            return min($inicio + 1, 1440);
        }

        return $total;
    }
}
