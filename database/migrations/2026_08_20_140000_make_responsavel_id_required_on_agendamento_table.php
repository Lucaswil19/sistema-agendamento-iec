<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $agendamentosSemResponsavel = DB::table('agendamento')
            ->whereNull('responsavel_id')
            ->orderBy('id')
            ->pluck('id');

        if ($agendamentosSemResponsavel->isNotEmpty()) {
            throw new RuntimeException(
                'Existem agendamentos sem responsável. Corrija os registros antes de migrar. IDs: ['
                .$agendamentosSemResponsavel->implode(', ')
                .'].'
            );
        }

        Schema::table('agendamento', function (Blueprint $table) {
            $table->dropForeign(['responsavel_id']);
        });

        Schema::table('agendamento', function (Blueprint $table) {
            $table->unsignedBigInteger('responsavel_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('agendamento', function (Blueprint $table) {
            $table->foreign('responsavel_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agendamento', function (Blueprint $table) {
            $table->dropForeign(['responsavel_id']);
        });

        Schema::table('agendamento', function (Blueprint $table) {
            $table->unsignedBigInteger('responsavel_id')
                ->nullable()
                ->change();
        });

        Schema::table('agendamento', function (Blueprint $table) {
            $table->foreign('responsavel_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
