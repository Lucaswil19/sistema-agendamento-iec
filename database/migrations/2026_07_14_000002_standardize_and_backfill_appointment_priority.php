<?php

use App\Services\PrioridadeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NIVEIS = ['baixa', 'media', 'alta', 'urgente'];

    public function up(): void
    {
        Schema::table('agendamento', function (Blueprint $table) {
            $table->enum('prioridade', self::NIVEIS)
                ->default('baixa')
                ->change();
        });

        $pontuacaoMinima = [
            'baixa' => 0,
            'media' => 3,
            'alta' => 6,
            'urgente' => 9,
        ];

        foreach ($pontuacaoMinima as $nivel => $pontos) {
            DB::table('agendamento')
                ->where('prioridade', $nivel)
                ->whereNull('justificativa_prioridade')
                ->update([
                    'pontuacao_prioridade' => $pontos,
                    'justificativa_prioridade' => 'Prioridade legada preservada. '
                        .'A pontuação mínima correspondente ao nível foi registrada porque '
                        .'os fatores considerados originalmente não estavam disponíveis.',
                ]);
        }

        // Registros encerrados preservam o nível histórico. Os abertos passam a refletir
        // imediatamente a composição e as idades atuais da família.
        app(PrioridadeService::class)->recalcularAgendamentosAbertos();
    }

    public function down(): void
    {
        Schema::table('agendamento', function (Blueprint $table) {
            $table->enum('prioridade', self::NIVEIS)
                ->default('media')
                ->change();
        });
    }
};
