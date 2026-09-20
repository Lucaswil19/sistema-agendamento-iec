<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamento', function (Blueprint $table) {
            $table->integer('pontuacao_prioridade')->default(0)->after('prioridade');
            $table->text('justificativa_prioridade')->nullable()->after('pontuacao_prioridade');
        });
    }

    public function down(): void
    {
        Schema::table('agendamento', function (Blueprint $table) {
            $table->dropColumn([
                'pontuacao_prioridade',
                'justificativa_prioridade',
            ]);
        });
    }
};
