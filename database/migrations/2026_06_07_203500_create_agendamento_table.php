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
        Schema::create('agendamento', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiario_id')
                ->constrained('beneficiarios')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('responsavel_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('tipo_acao', 100);

            $table->date('data_agendada');
            $table->time('hora_agendada');
            $table->time('hora_final_agendada')->nullable();

            $table->enum('status', [
                'agendado',
                'completado',
                'cancelado',
                'perdido',
                'reagendado',
            ])->default('agendado');

            $table->enum('prioridade', [
                'baixa',
                'media',
                'alta',
                'urgente',
            ])->default('media');

            $table->string('local')->nullable();
            $table->text('notas')->nullable();
            $table->text('motivo_cancelamento')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendamento');
    }
};
