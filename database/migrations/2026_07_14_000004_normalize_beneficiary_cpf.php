<?php

use App\Support\Cpf;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $normalizedById = [];
        $ownerByCpf = [];
        $invalidIds = [];
        $duplicateIds = [];

        foreach (DB::table('beneficiarios')->orderBy('id')->cursor() as $beneficiario) {
            $normalized = Cpf::normalize($beneficiario->cpf);

            if (! Cpf::isValid($normalized)) {
                $invalidIds[] = $beneficiario->id;

                continue;
            }

            if (isset($ownerByCpf[$normalized])) {
                $duplicateIds[] = $ownerByCpf[$normalized];
                $duplicateIds[] = $beneficiario->id;

                continue;
            }

            $ownerByCpf[$normalized] = $beneficiario->id;
            $normalizedById[$beneficiario->id] = $normalized;
        }

        if ($invalidIds !== [] || $duplicateIds !== []) {
            throw new RuntimeException(sprintf(
                'Não foi possível normalizar os CPFs. Corrija os registros antes de migrar. '
                .'CPFs inválidos (beneficiários): [%s]. CPFs duplicados após normalização (beneficiários): [%s].',
                implode(', ', array_slice(array_unique($invalidIds), 0, 20)),
                implode(', ', array_slice(array_unique($duplicateIds), 0, 20))
            ));
        }

        DB::transaction(function () use ($normalizedById): void {
            foreach ($normalizedById as $id => $cpf) {
                DB::table('beneficiarios')
                    ->where('id', $id)
                    ->update(['cpf' => $cpf]);
            }
        });
    }

    public function down(): void
    {
        $formattedById = [];

        foreach (DB::table('beneficiarios')->select(['id', 'cpf'])->orderBy('id')->cursor() as $beneficiario) {
            $formattedById[$beneficiario->id] = Cpf::format($beneficiario->cpf);
        }

        DB::transaction(function () use ($formattedById): void {
            foreach ($formattedById as $id => $cpf) {
                DB::table('beneficiarios')
                    ->where('id', $id)
                    ->update(['cpf' => $cpf]);
            }
        });
    }
};
