<?php

namespace App\Policies;

use App\Models\Beneficiario;
use App\Models\User;

class BeneficiarioPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['lider', 'secretaria', 'voluntario'], true);
    }

    public function view(User $user, Beneficiario $beneficiario): bool
    {
        if (in_array($user->role, ['lider', 'secretaria'], true)) {
            return true;
        }

        return $user->role === 'voluntario'
            && $beneficiario->possuiAtendimentoAtribuidoA($user);
    }
}
