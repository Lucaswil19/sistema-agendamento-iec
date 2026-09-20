<?php

namespace App\Services;

use App\Models\Beneficiario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrioridadeService
{
    private const STATUS_ABERTOS = ['agendado', 'reagendado'];

    public function calcular(Beneficiario $beneficiario): array
    {
        $beneficiario->loadMissing('historicoFamiliar');

        $membros = $beneficiario->historicoFamiliar;

        $quantidadeDependentes = $membros->count();

        $numeroMembros = max(1, $quantidadeDependentes + 1);

        $quantidadeCriancas = 0;
        $quantidadeIdosos = 0;
        $quantidadeDeficiencias = 0;
        $quantidadeProblemasSaude = 0;

        $idadeBeneficiario = $beneficiario->idade;

        if ($idadeBeneficiario !== null && $idadeBeneficiario <= 12) {
            $quantidadeCriancas++;
        }

        if ($idadeBeneficiario !== null && $idadeBeneficiario >= 60) {
            $quantidadeIdosos++;
        }

        if ($beneficiario->possui_deficiencia) {
            $quantidadeDeficiencias++;
        }

        if ($beneficiario->possui_problema_saude) {
            $quantidadeProblemasSaude++;
        }

        foreach ($membros as $membro) {
            $idade = $membro->idade;

            if ($idade !== null && $idade <= 12) {
                $quantidadeCriancas++;
            }

            if ($idade !== null && $idade >= 60) {
                $quantidadeIdosos++;
            }

            if ($membro->possui_deficiencia) {
                $quantidadeDeficiencias++;
            }

            if ($membro->possui_problema_saude) {
                $quantidadeProblemasSaude++;
            }
        }

        $pontos = 0;
        $motivos = [];

        if ($numeroMembros >= 7) {
            $pontos += 3;
            $motivos[] = "família numerosa com {$numeroMembros} membros";
        } elseif ($numeroMembros >= 5) {
            $pontos += 2;
            $motivos[] = "família com {$numeroMembros} membros";
        } elseif ($numeroMembros >= 3) {
            $pontos += 1;
            $motivos[] = "família com {$numeroMembros} membros";
        }

        if ($quantidadeCriancas > 0) {
            $pontos += $quantidadeCriancas;
            $motivos[] = "{$quantidadeCriancas} criança(s) no grupo familiar";
        }

        if ($quantidadeIdosos > 0) {
            $pontos += $quantidadeIdosos * 2;
            $motivos[] = "{$quantidadeIdosos} idoso(s) no grupo familiar";
        }

        if ($quantidadeDeficiencias > 0) {
            $pontos += $quantidadeDeficiencias * 3;
            $motivos[] = "{$quantidadeDeficiencias} pessoa(s) com deficiência";
        }

        if ($quantidadeProblemasSaude > 0) {
            $pontos += $quantidadeProblemasSaude * 2;
            $motivos[] = "{$quantidadeProblemasSaude} pessoa(s) com doença ou problema de saúde";
        }

        return [
            'nivel' => $this->definirNivel($pontos),
            'pontos' => $pontos,
            'justificativa' => empty($motivos)
                ? 'Prioridade calculada automaticamente sem fatores agravantes identificados.'
                : 'Prioridade calculada automaticamente considerando: '.implode('; ', $motivos).'.',
        ];
    }

    public function recalcularAgendamentosAbertos(?int $beneficiarioId = null): int
    {
        $totalAtualizado = 0;
        $usaVersaoOtimista = Schema::hasColumn('agendamento', 'lock_version');

        Beneficiario::query()
            ->when(
                $beneficiarioId !== null,
                fn ($query) => $query->whereKey($beneficiarioId)
            )
            ->whereHas(
                'agendamentos',
                fn ($query) => $query->whereIn('status', self::STATUS_ABERTOS)
            )
            ->select('id')
            ->chunkById(100, function ($beneficiarios) use (&$totalAtualizado, $usaVersaoOtimista): void {
                foreach ($beneficiarios as $beneficiarioResumo) {
                    $totalAtualizado += DB::transaction(function () use (
                        $beneficiarioResumo,
                        $usaVersaoOtimista
                    ): int {
                        $beneficiario = Beneficiario::query()
                            ->with('historicoFamiliar')
                            ->lockForUpdate()
                            ->findOrFail($beneficiarioResumo->getKey());
                        $prioridade = $this->calcular($beneficiario);

                        $atualizacoes = [
                            'prioridade' => $prioridade['nivel'],
                            'pontuacao_prioridade' => $prioridade['pontos'],
                            'justificativa_prioridade' => $prioridade['justificativa'],
                        ];

                        if ($usaVersaoOtimista) {
                            $atualizacoes['lock_version'] = DB::raw('lock_version + 1');
                        }

                        return $beneficiario->agendamentos()
                            ->whereIn('status', self::STATUS_ABERTOS)
                            ->update($atualizacoes);
                    });
                }
            });

        return $totalAtualizado;
    }

    private function definirNivel(int $pontos): string
    {
        if ($pontos >= 9) {
            return 'urgente';
        }

        if ($pontos >= 6) {
            return 'alta';
        }

        if ($pontos >= 3) {
            return 'media';
        }

        return 'baixa';
    }
}
