<?php

namespace App\Http\Requests;

use App\Models\Beneficiario;
use Illuminate\Foundation\Http\FormRequest;

class BeneficiarioShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        $beneficiario = $this->route('beneficiario');

        return $beneficiario instanceof Beneficiario
            && ($this->user()?->can('view', $beneficiario) ?? false);
    }

    public function rules(): array
    {
        return [
            'familia_page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'agendamentos_page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'acoes_page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'familia_page.integer' => 'A página do histórico familiar é inválida.',
            'agendamentos_page.integer' => 'A página dos agendamentos é inválida.',
            'acoes_page.integer' => 'A página dos atendimentos realizados é inválida.',
        ];
    }
}
