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
        Schema::create('historico_familiar', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiario_id')
                ->constrained('beneficiarios')
                ->cascadeOnDelete();

            $table->integer('numero_dependentes')->nullable();
            $table->string('nome');
            $table->date('data_nascimento')->nullable();
            $table->integer('idade')->nullable();
            $table->string('parentesco', 100)->nullable();
            $table->text('observacoes')->nullable();

            $table->timestamps();

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_familiar');
    }
};
