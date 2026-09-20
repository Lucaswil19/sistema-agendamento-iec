<?php

namespace App\Http\Controllers;

use App\Http\Requests\BeneficiarioIndexRequest;
use App\Http\Requests\BeneficiarioRequest;
use App\Http\Requests\BeneficiarioShowRequest;
use App\Models\Beneficiario;
use App\Services\PrioridadeService;
use App\Support\Cpf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BeneficiariosController extends Controller
{
    public function index(BeneficiarioIndexRequest $request)
    {
        $filtros = $request->validated();
        $status = $filtros['status'] ?? 'ativo';
        $usuario = $request->user();
        $voluntarioId = $usuario->role === 'voluntario'
            ? $usuario->getKey()
            : null;

        $beneficiarios = Beneficiario::query()
            ->visiveisPara($usuario)
            ->withCount([
                'historicoFamiliar',
                'agendamentos' => fn ($query) => $query->when(
                    $voluntarioId !== null,
                    fn ($query) => $query->where('responsavel_id', $voluntarioId)
                ),
            ])
            ->when($status === 'ativo', function ($query) {
                $query->where('is_active', true);
            })
            ->when($status === 'inativo', function ($query) {
                $query->where('is_active', false);
            })
            ->when(! empty($filtros['search']), function ($query) use ($filtros) {
                $search = $filtros['search'];
                $cpfSearch = Cpf::normalize($search);

                $query->where(function ($q) use ($search, $cpfSearch) {
                    $q->where('nome_beneficiario', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telefone', 'like', "%{$search}%");

                    if ($cpfSearch !== '') {
                        $q->orWhere('cpf', 'like', "%{$cpfSearch}%");
                    }
                });
            })
            ->orderBy('nome_beneficiario')
            ->paginate(8)
            ->withQueryString();

        return view('Beneficiarios.home', compact('beneficiarios'));
    }

    public function create()
    {
        return view('Beneficiarios.CadastrarBeneficiario');
    }

    public function store(BeneficiarioRequest $request)
    {
        $dados = $this->prepararDados($request);

        $dados['data_nascimento'] = Carbon::createFromFormat('d/m/Y', $dados['data_nascimento'])
            ->format('Y-m-d');

        $beneficiario = DB::transaction(fn () => Beneficiario::create($dados));

        return redirect()
            ->route('beneficiarios.show', $beneficiario)
            ->with('success', 'Beneficiário cadastrado com sucesso. Agora cadastre ou confira o histórico familiar.');
    }

    public function show(BeneficiarioShowRequest $request, Beneficiario $beneficiario)
    {
        $voluntarioId = $request->user()->role === 'voluntario'
            ? $request->user()->getKey()
            : null;

        // Regra A: a autorização da ficha libera todo o histórico familiar.
        $historicoFamiliar = $beneficiario->historicoFamiliar()
            ->orderBy('nome')
            ->paginate(15, ['*'], 'familia_page')
            ->withQueryString();

        $agendamentos = $beneficiario->agendamentos()
            ->when(
                $voluntarioId !== null,
                fn ($query) => $query->where('responsavel_id', $voluntarioId)
            )
            ->orderByDesc('data_agendada')
            ->orderByDesc('hora_agendada')
            ->paginate(15, ['*'], 'agendamentos_page')
            ->withQueryString();

        $historicoAcoes = $beneficiario->historicoAcoes()
            ->when($voluntarioId !== null, function ($query) use ($voluntarioId) {
                $query->whereHas(
                    'agendamento',
                    fn ($query) => $query->where('responsavel_id', $voluntarioId)
                );
            })
            ->orderByDesc('data_atendimento')
            ->paginate(15, ['*'], 'acoes_page')
            ->withQueryString();

        return view('Beneficiarios.MostrarBeneficiario', compact(
            'beneficiario',
            'historicoFamiliar',
            'agendamentos',
            'historicoAcoes'
        ));
    }

    public function edit(Beneficiario $beneficiario)
    {
        return view('Beneficiarios.EditarBeneficiario', compact('beneficiario'));
    }

    public function update(BeneficiarioRequest $request, Beneficiario $beneficiario, PrioridadeService $prioridadeService)
    {
        $dados = $this->prepararDados($request);

        $dados['data_nascimento'] = Carbon::createFromFormat('d/m/Y', $dados['data_nascimento'])
            ->format('Y-m-d');

        DB::transaction(function () use ($beneficiario, $dados, $prioridadeService): void {
            $beneficiarioBloqueado = Beneficiario::query()
                ->lockForUpdate()
                ->findOrFail($beneficiario->getKey());
            $beneficiarioBloqueado->update($dados);
            $this->recalcularAgendamentosAbertos($beneficiarioBloqueado, $prioridadeService);
        });

        return redirect()
            ->route('beneficiarios.show', $beneficiario)
            ->with('success', 'Beneficiário atualizado com sucesso.');
    }

    private function recalcularAgendamentosAbertos(
        Beneficiario $beneficiario,
        PrioridadeService $prioridadeService
    ): void {
        $beneficiario->refresh();
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

    public function inativar(Beneficiario $beneficiario)
    {
        $jaEstavaInativo = DB::transaction(function () use ($beneficiario): bool {
            $beneficiarioBloqueado = Beneficiario::query()
                ->lockForUpdate()
                ->findOrFail($beneficiario->getKey());

            if (! $beneficiarioBloqueado->is_active) {
                return true;
            }

            $beneficiarioBloqueado->is_active = false;
            $beneficiarioBloqueado->save();

            return false;
        });

        if ($jaEstavaInativo) {
            return redirect()
                ->route('home')
                ->with('success', 'O beneficiário já estava inativo.');
        }

        return redirect()
            ->route('home')
            ->with('success', 'Beneficiário inativado com sucesso. O cadastro e seus históricos foram preservados.');
    }

    public function reativar(Beneficiario $beneficiario)
    {
        $jaEstavaAtivo = DB::transaction(function () use ($beneficiario): bool {
            $beneficiarioBloqueado = Beneficiario::query()
                ->lockForUpdate()
                ->findOrFail($beneficiario->getKey());

            if ($beneficiarioBloqueado->is_active) {
                return true;
            }

            $beneficiarioBloqueado->is_active = true;
            $beneficiarioBloqueado->save();

            return false;
        });

        if ($jaEstavaAtivo) {
            return redirect()
                ->route('beneficiarios.show', $beneficiario)
                ->with('success', 'O beneficiário já estava ativo.');
        }

        return redirect()
            ->route('beneficiarios.show', $beneficiario)
            ->with('success', 'Beneficiário reativado com sucesso.');
    }

    private function prepararDados(BeneficiarioRequest $request): array
    {
        $dados = $request->validated();
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
}
