<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('historico_acoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiario_id')
                ->constrained('beneficiarios')
                ->cascadeOnDelete();

            $table->foreignId('agendamento_id')
                ->nullable()
                ->constrained('agendamento')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('descricao');
            $table->string('tipo_atendimento', 100)->nullable();
            $table->date('data_atendimento');
            $table->text('encaminhamentos')->nullable();
            $table->text('observacoes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_acoes');
    }
};
