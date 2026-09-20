<?php

namespace App\Http\Controllers;

use App\Http\Requests\RelatorioAtendimentosRequest;
use App\Http\Requests\RelatorioBeneficiariosRequest;
use App\Models\Beneficiario;
use App\Models\Historico_acoes;
use Carbon\Carbon;

class RelatorioController extends Controller
{
    public function atendimentos(RelatorioAtendimentosRequest $request)
    {
        $filtros = $request->validated();

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

        if ($dataInicio && $dataFim && $dataInicio > $dataFim) {
            return back()
                ->withErrors([
                    'data_inicio' =>
                        'A data inicial não pode ser posterior à data final.',
                ])
                ->withInput();
        }

        $atendimentosFiltrados = Historico_acoes::query()
            ->when($dataInicio, function ($query) use ($dataInicio) {
                $query->where(
                    'data_atendimento',
                    '>=',
                    $dataInicio
                );
            })
            ->when($dataFim, function ($query) use ($dataFim) {
                $query->where(
                    'data_atendimento',
                    '<',
                    Carbon::parse($dataFim)->addDay()->toDateString()
                );
            });

        $totais = (clone $atendimentosFiltrados)
            ->leftJoin('agendamento', 'agendamento.id', '=', 'historico_acoes.agendamento_id')
            ->selectRaw('COUNT(*) AS atendimentos')
            ->selectRaw('COUNT(DISTINCT historico_acoes.beneficiario_id) AS beneficiarios')
            ->selectRaw('COALESCE(SUM(CASE WHEN agendamento.prioridade = ? THEN 1 ELSE 0 END), 0) AS urgentes', ['urgente'])
            ->selectRaw("COALESCE(SUM(CASE WHEN TRIM(historico_acoes.encaminhamentos) <> '' THEN 1 ELSE 0 END), 0) AS encaminhamentos")
            ->toBase()
            ->first();

        $totalAtendimentos = (int) $totais->atendimentos;
        $totalBeneficiarios = (int) $totais->beneficiarios;
        $totalUrgentes = (int) $totais->urgentes;
        $totalComEncaminhamento = (int) $totais->encaminhamentos;

        $atendimentos = (clone $atendimentosFiltrados)
            ->with([
                'beneficiario',
                'agendamento.responsavel',
                'usuario',
            ])
            ->orderByDesc('data_atendimento')
            ->orderByDesc('id')
            ->paginate(20, total: $totalAtendimentos)
            ->withQueryString();

        return view('Relatorios.atendimentos', compact(
            'totalAtendimentos',
            'totalBeneficiarios',
            'totalUrgentes',
            'totalComEncaminhamento',
            'atendimentos'
        ));
    }

    public function beneficiarios(RelatorioBeneficiariosRequest $request)
    {
        $beneficiarios = Beneficiario::query()
            ->select([
                'id',
                'nome_beneficiario',
            ])
            ->withCount([
                'historicoFamiliar as numero_familiares',
                'historicoAcoes as numero_acoes',
            ])
            ->withMax(
                'historicoAcoes as data_ultima_acao',
                'data_atendimento'
            )
            ->withCasts([
                'data_ultima_acao' => 'date',
            ])
            ->orderBy('nome_beneficiario')
            ->paginate(20)
            ->withQueryString();

        return view(
            'Relatorios.beneficiarios',
            compact('beneficiarios')
        );
    }
}
