<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historico_acoes', function (Blueprint $table) {
            $table->index(['data_atendimento', 'id'], 'historico_acoes_data_id_index');
            $table->index(['beneficiario_id', 'data_atendimento'], 'historico_acoes_beneficiario_data_index');
        });
    }

    public function down(): void
    {
        Schema::table('historico_acoes', function (Blueprint $table) {
            $table->dropIndex('historico_acoes_data_id_index');
            $table->dropIndex('historico_acoes_beneficiario_data_index');
        });
    }
};
