<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelarAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'motivo_cancelamento' => ['nullable', 'string', 'max:2000'],
            'status' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'lock_version.required' => 'Atualize a página antes de cancelar o agendamento.',
            'lock_version.integer' => 'A versão do agendamento é inválida. Atualize a página.',
            'motivo_cancelamento.max' => 'O motivo do cancelamento pode ter no máximo 2.000 caracteres.',
            'status.prohibited' => 'O status é definido pela ação de cancelamento e não pode ser enviado manualmente.',
        ];
    }
}
