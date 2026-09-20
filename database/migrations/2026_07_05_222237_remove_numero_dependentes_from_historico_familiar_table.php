<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historico_familiar', function (Blueprint $table) {
            $table->dropColumn('numero_dependentes');
        });
    }

    public function down(): void
    {
        Schema::table('historico_familiar', function (Blueprint $table) {
            $table->integer('numero_dependentes')->nullable();
        });

        $quantidades = DB::table('historico_familiar')
            ->select('beneficiario_id', DB::raw('COUNT(*) AS total'))
            ->groupBy('beneficiario_id')
            ->get();

        foreach ($quantidades as $quantidade) {
            DB::table('historico_familiar')
                ->where('beneficiario_id', $quantidade->beneficiario_id)
                ->update(['numero_dependentes' => $quantidade->total]);
        }
    }
};
