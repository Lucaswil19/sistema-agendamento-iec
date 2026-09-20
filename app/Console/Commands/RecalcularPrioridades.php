<?php

namespace App\Console\Commands;

use App\Models\Beneficiario;
use App\Services\PrioridadeService;
use Illuminate\Console\Command;

class RecalcularPrioridades extends Command
{
    protected $signature = 'prioridades:recalcular
                            {--beneficiario= : Recalcula somente o beneficiário informado}';

    protected $description = 'Recalcula a prioridade dos agendamentos abertos';

    public function handle(PrioridadeService $prioridadeService): int
    {
        $opcaoBeneficiario = $this->option('beneficiario');
        $beneficiarioId = null;

        if ($opcaoBeneficiario !== null) {
            if (! ctype_digit((string) $opcaoBeneficiario) || (int) $opcaoBeneficiario < 1) {
                $this->error('O identificador do beneficiário deve ser um número inteiro positivo.');

                return self::INVALID;
            }

            $beneficiarioId = (int) $opcaoBeneficiario;

            if (! Beneficiario::query()->whereKey($beneficiarioId)->exists()) {
                $this->error('Beneficiário não encontrado.');

                return self::FAILURE;
            }
        }

        $total = $prioridadeService->recalcularAgendamentosAbertos($beneficiarioId);

        $this->info("Prioridades recalculadas em {$total} agendamento(s) aberto(s).");

        return self::SUCCESS;
    }
}
