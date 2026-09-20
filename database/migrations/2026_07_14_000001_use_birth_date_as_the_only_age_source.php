<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $familiaresInvalidos = DB::table('historico_familiar')
            ->where(function ($query): void {
                $query->whereNull('data_nascimento')
                    ->orWhere('data_nascimento', '>', now()->toDateString())
                    ->orWhereNull('parentesco')
                    ->orWhereRaw("TRIM(parentesco) = ''");
            })
            ->orderBy('id')
            ->limit(20)
            ->pluck('id');

        $beneficiariosInvalidos = DB::table('beneficiarios')
            ->where(function ($query): void {
                $query->whereNull('data_nascimento')
                    ->orWhere('data_nascimento', '>', now()->toDateString());
            })
            ->orderBy('id')
            ->limit(20)
            ->pluck('id');

        if ($familiaresInvalidos->isNotEmpty() || $beneficiariosInvalidos->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'Não foi possível consolidar data_nascimento como fonte de idade. '
                .'Corrija datas ausentes/futuras e parentescos vazios. Familiares: [%s]. Beneficiários: [%s].',
                $familiaresInvalidos->implode(', '),
                $beneficiariosInvalidos->implode(', ')
            ));
        }

        DB::table('historico_familiar')
            ->update(['parentesco' => DB::raw('TRIM(parentesco)')]);

        Schema::table('historico_familiar', function (Blueprint $table) {
            $table->date('data_nascimento')->nullable(false)->change();
            $table->string('parentesco', 100)->nullable(false)->change();
        });

        Schema::table('historico_familiar', function (Blueprint $table) {
            $table->dropColumn('idade');
        });
    }

    public function down(): void
    {
        Schema::table('historico_familiar', function (Blueprint $table) {
            $table->integer('idade')->nullable()->after('data_nascimento');
        });

        DB::table('historico_familiar')
            ->select(['id', 'data_nascimento'])
            ->orderBy('id')
            ->chunkById(500, function ($familiares): void {
                foreach ($familiares as $familiar) {
                    DB::table('historico_familiar')
                        ->where('id', $familiar->id)
                        ->update([
                            'idade' => CarbonImmutable::parse($familiar->data_nascimento)->age,
                        ]);
                }
            });

        Schema::table('historico_familiar', function (Blueprint $table) {
            $table->date('data_nascimento')->nullable()->change();
            $table->string('parentesco', 100)->nullable()->change();
        });
    }
};
