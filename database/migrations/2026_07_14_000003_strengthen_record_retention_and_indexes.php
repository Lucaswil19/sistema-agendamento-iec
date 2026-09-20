<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $historicosInconsistentes = DB::table('historico_acoes as historico')
            ->join('agendamento', 'agendamento.id', '=', 'historico.agendamento_id')
            ->whereColumn('historico.beneficiario_id', '!=', 'agendamento.beneficiario_id')
            ->orderBy('historico.id')
            ->limit(20)
            ->pluck('historico.id');

        $agendamentosDuplicados = DB::table('historico_acoes')
            ->select('agendamento_id')
            ->whereNotNull('agendamento_id')
            ->groupBy('agendamento_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('agendamento_id')
            ->limit(20)
            ->pluck('agendamento_id');

        if (
            $historicosInconsistentes->isNotEmpty()
            || $agendamentosDuplicados->isNotEmpty()
        ) {
            throw new RuntimeException(sprintf(
                'Existem históricos cujo beneficiário diverge do agendamento. '
                .'Também pode haver mais de um histórico para o mesmo agendamento. '
                .'Corrija os registros antes de migrar. Históricos divergentes: [%s]. '
                .'Agendamentos duplicados: [%s].',
                $historicosInconsistentes->implode(', '),
                $agendamentosDuplicados->implode(', ')
            ));
        }

        Schema::table('agendamento', function (Blueprint $table) {
            $table->dropForeign(['beneficiario_id']);
            $table->dropForeign(['user_id']);

            $table->foreign('beneficiario_id')
                ->references('id')
                ->on('beneficiarios')
                ->restrictOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->unique(
                ['id', 'beneficiario_id'],
                'agendamento_id_beneficiario_unique'
            );
            $table->index(
                ['data_agendada', 'status'],
                'agendamento_data_status_index'
            );
            $table->index(
                ['beneficiario_id', 'status'],
                'agendamento_beneficiario_status_index'
            );
            $table->index(
                ['responsavel_id', 'data_agendada', 'status'],
                'agendamento_responsavel_data_status_index'
            );
        });

        Schema::table('historico_familiar', function (Blueprint $table) {
            $table->dropForeign(['beneficiario_id']);
            $table->foreign('beneficiario_id')
                ->references('id')
                ->on('beneficiarios')
                ->restrictOnDelete();
        });

        Schema::table('historico_acoes', function (Blueprint $table) {
            $table->dropForeign(['beneficiario_id']);
            $table->dropForeign(['agendamento_id']);
            $table->dropForeign(['user_id']);

            $table->foreign('beneficiario_id')
                ->references('id')
                ->on('beneficiarios')
                ->restrictOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['agendamento_id', 'beneficiario_id'])
                ->references(['id', 'beneficiario_id'])
                ->on('agendamento')
                ->restrictOnDelete();
            $table->unique(
                'agendamento_id',
                'historico_acoes_agendamento_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('historico_acoes', function (Blueprint $table) {
            $table->dropForeign(['agendamento_id', 'beneficiario_id']);
            $table->dropForeign(['beneficiario_id']);
            $table->dropForeign(['user_id']);
            $table->dropUnique('historico_acoes_agendamento_unique');

            $table->foreign('beneficiario_id')
                ->references('id')
                ->on('beneficiarios')
                ->cascadeOnDelete();
            $table->foreign('agendamento_id')
                ->references('id')
                ->on('agendamento')
                ->nullOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('historico_familiar', function (Blueprint $table) {
            $table->dropForeign(['beneficiario_id']);
            $table->foreign('beneficiario_id')
                ->references('id')
                ->on('beneficiarios')
                ->cascadeOnDelete();
        });

        Schema::table('agendamento', function (Blueprint $table) {
            $table->dropForeign(['beneficiario_id']);
            $table->dropForeign(['user_id']);
            $table->dropUnique('agendamento_id_beneficiario_unique');
            $table->dropIndex('agendamento_data_status_index');
            $table->dropIndex('agendamento_beneficiario_status_index');
            $table->dropIndex('agendamento_responsavel_data_status_index');

            $table->foreign('beneficiario_id')
                ->references('id')
                ->on('beneficiarios')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
