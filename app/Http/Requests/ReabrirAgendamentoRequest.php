<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReabrirAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'data_agendada' => ['required', 'date_format:d/m/Y'],
            'hora_agendada' => ['required', 'date_format:H:i'],
            'hora_final_agendada' => ['nullable', 'date_format:H:i', 'after:hora_agendada'],
            'status' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'lock_version.required' => 'Atualize a página antes de reabrir o agendamento.',
            'lock_version.integer' => 'A versão do agendamento é inválida. Atualize a página.',
            'data_agendada.required' => 'Informe a nova data do agendamento.',
            'data_agendada.date_format' => 'A data deve estar no formato dd/mm/aaaa.',
            'hora_agendada.required' => 'Informe o novo horário inicial.',
            'hora_agendada.date_format' => 'O horário inicial deve estar no formato hh:mm.',
            'hora_final_agendada.date_format' => 'O horário final deve estar no formato hh:mm.',
            'hora_final_agendada.after' => 'O horário final deve ser posterior ao horário inicial.',
            'status.prohibited' => 'O status é definido pela ação de reabertura e não pode ser enviado manualmente.',
        ];
    }
}
