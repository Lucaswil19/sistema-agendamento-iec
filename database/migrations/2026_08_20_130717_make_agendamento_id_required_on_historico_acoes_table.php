<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $historicosSemAgendamento = DB::table('historico_acoes')
            ->whereNull('agendamento_id')
            ->orderBy('id')
            ->limit(20)
            ->pluck('id');

        if ($historicosSemAgendamento->isNotEmpty()) {
            throw new RuntimeException(
                'Existem históricos sem agendamento. Corrija os registros antes de migrar. IDs: ['
                .$historicosSemAgendamento->implode(', ')
                .'].'
            );
        }

        Schema::table('historico_acoes', function (Blueprint $table) {
            $table->dropForeign(['agendamento_id', 'beneficiario_id']);
        });

        Schema::table('historico_acoes', function (Blueprint $table) {
            $table->unsignedBigInteger('agendamento_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('historico_acoes', function (Blueprint $table) {
            $table->foreign(['agendamento_id', 'beneficiario_id'])
                ->references(['id', 'beneficiario_id'])
                ->on('agendamento')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('historico_acoes', function (Blueprint $table) {
            $table->dropForeign(['agendamento_id', 'beneficiario_id']);
        });

        Schema::table('historico_acoes', function (Blueprint $table) {
            $table->unsignedBigInteger('agendamento_id')
                ->nullable()
                ->change();
        });

        Schema::table('historico_acoes', function (Blueprint $table) {
            $table->foreign(['agendamento_id', 'beneficiario_id'])
                ->references(['id', 'beneficiario_id'])
                ->on('agendamento')
                ->restrictOnDelete();
        });
    }
};