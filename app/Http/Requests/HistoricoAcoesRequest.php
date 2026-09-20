<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class HistoricoAcoesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:5000'],
            'tipo_atendimento' => ['nullable', 'string', 'max:100'],
            'data_atendimento' => [
                'required',
                'date_format:d/m/Y',
                function (string $attribute, mixed $value, Closure $fail): void {
                    try {
                        if (Carbon::createFromFormat('d/m/Y', (string) $value)->startOfDay()->isAfter(today())) {
                            $fail('A data do atendimento não pode ser futura.');
                        }
                    } catch (\Throwable) {
                        // A regra date_format já produz a mensagem adequada.
                    }
                },
            ],
            'encaminhamentos' => ['nullable', 'string', 'max:5000'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'Descreva o atendimento realizado.',
            'descricao.max' => 'A descrição pode ter no máximo 5.000 caracteres.',
            'tipo_atendimento.max' => 'O tipo de atendimento pode ter no máximo 100 caracteres.',
            'data_atendimento.required' => 'Informe a data do atendimento.',
            'data_atendimento.date_format' => 'A data deve estar no formato dd/mm/aaaa. Exemplo: 15/07/2026.',
            'encaminhamentos.max' => 'Os encaminhamentos podem ter no máximo 5.000 caracteres.',
            'observacoes.max' => 'As observações podem ter no máximo 5.000 caracteres.',
        ];
    }
}
