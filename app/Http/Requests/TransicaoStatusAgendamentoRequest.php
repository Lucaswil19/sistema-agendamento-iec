<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransicaoStatusAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'status' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'lock_version.required' => 'Atualize a página antes de executar esta ação.',
            'lock_version.integer' => 'A versão do agendamento é inválida. Atualize a página.',
            'status.prohibited' => 'O status é definido pela ação escolhida e não pode ser enviado manualmente.',
        ];
    }
}
