<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiarios', function (Blueprint $table) {
            $table->boolean('possui_problema_saude')
                ->default(false)
                ->after('data_nascimento');

            $table->text('descricao_problema_saude')
                ->nullable()
                ->after('possui_problema_saude');

            $table->boolean('possui_deficiencia')
                ->default(false)
                ->after('descricao_problema_saude');

            $table->text('descricao_deficiencia')
                ->nullable()
                ->after('possui_deficiencia');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiarios', function (Blueprint $table) {
            $table->dropColumn([
                'possui_problema_saude',
                'descricao_problema_saude',
                'possui_deficiencia',
                'descricao_deficiencia',
            ]);
        });
    }
};
