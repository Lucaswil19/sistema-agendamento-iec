<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agendamento = $this->route('agendamento');
        $beneficiarioAtual = $agendamento?->beneficiario_id;
        $responsavelAtual = $agendamento?->responsavel_id;

        $regras = [
            'beneficiario_id' => [
                'required',
                'integer',
                Rule::exists('beneficiarios', 'id')->where(function ($query) use ($beneficiarioAtual) {
                    $query->where(function ($query) use ($beneficiarioAtual) {
                        $query->where('is_active', true);

                        if ($beneficiarioAtual !== null) {
                            $query->orWhere('id', $beneficiarioAtual);
                        }
                    });
                }),
            ],
            'responsavel_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($responsavelAtual) {
                    $query->whereIn('role', ['lider', 'voluntario'])
                        ->where(function ($query) use ($responsavelAtual) {
                            $query->where('is_active', true);

                            if ($responsavelAtual !== null) {
                                $query->orWhere('id', $responsavelAtual);
                            }
                        });
                }),
            ],
            'tipo_acao' => ['required', 'string', 'max:100'],
            'data_agendada' => ['required', 'date_format:d/m/Y'],
            'hora_agendada' => ['required', 'date_format:H:i'],
            'hora_final_agendada' => ['nullable', 'date_format:H:i', 'after:hora_agendada'],
            'local' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $regras['status'] = ['prohibited'];
            $regras['lock_version'] = ['required', 'integer', 'min:0'];
        }

        return $regras;
    }

    public function messages(): array
    {
        return [
            'beneficiario_id.required' => 'Selecione o beneficiário que receberá o atendimento.',
            'beneficiario_id.exists' => 'Selecione um beneficiário ativo ou mantenha o vínculo atual.',
            'responsavel_id.required' => 'Selecione o responsável pela ação.',
            'responsavel_id.exists' => 'Selecione um responsável ativo com perfil de líder ou voluntário, ou mantenha o vínculo atual.',
            'tipo_acao.required' => 'Informe o tipo de ação social.',
            'tipo_acao.max' => 'O tipo de ação pode ter no máximo 100 caracteres.',
            'data_agendada.required' => 'Informe a data do agendamento.',
            'data_agendada.date_format' => 'A data deve estar no formato dd/mm/aaaa. Exemplo: 15/07/2026.',
            'hora_agendada.required' => 'Informe o horário inicial da ação.',
            'hora_agendada.date_format' => 'O horário inicial deve estar no formato hh:mm. Exemplo: 14:30.',
            'hora_final_agendada.date_format' => 'O horário final deve estar no formato hh:mm. Exemplo: 16:00.',
            'hora_final_agendada.after' => 'O horário final deve ser posterior ao horário inicial.',
            'status.prohibited' => 'O status não pode ser alterado pela edição. Use a ação específica para a transição desejada.',
            'lock_version.required' => 'Atualize a página antes de salvar o agendamento.',
            'lock_version.integer' => 'A versão do agendamento é inválida. Atualize a página.',
            'local.max' => 'O local pode ter no máximo 255 caracteres.',
            'notas.max' => 'As notas podem ter no máximo 5.000 caracteres.',
        ];
    }
}
