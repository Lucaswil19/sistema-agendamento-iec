<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HistoricoFamiliarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'data_nascimento' => [
                'required',
                'date_format:d/m/Y',
                function (string $attribute, mixed $value, Closure $fail): void {
                    try {
                        if (Carbon::createFromFormat('d/m/Y', (string) $value)->startOfDay()->isAfter(today())) {
                            $fail('A data de nascimento não pode ser futura.');
                        }
                    } catch (\Throwable) {
                        // A regra date_format já produz a mensagem adequada.
                    }
                },
            ],
            'parentesco' => ['required', 'string', 'max:100'],
            'possui_problema_saude' => ['required', 'boolean'],
            'descricao_problema_saude' => [
                Rule::excludeIf(fn (): bool => ! $this->boolean('possui_problema_saude')),
                Rule::requiredIf(fn (): bool => $this->boolean('possui_problema_saude')),
                'nullable',
                'string',
                'max:2000',
            ],
            'possui_deficiencia' => ['required', 'boolean'],
            'descricao_deficiencia' => [
                Rule::excludeIf(fn (): bool => ! $this->boolean('possui_deficiencia')),
                Rule::requiredIf(fn (): bool => $this->boolean('possui_deficiencia')),
                'nullable',
                'string',
                'max:2000',
            ],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do membro familiar.',
            'data_nascimento.required' => 'Informe a data de nascimento do membro familiar.',
            'data_nascimento.date_format' => 'A data de nascimento deve estar no formato dd/mm/aaaa.',
            'parentesco.required' => 'Informe o parentesco do membro familiar.',
            'possui_problema_saude.required' => 'Informe se o membro possui problema de saúde.',
            'descricao_problema_saude.required' => 'Descreva o problema de saúde informado.',
            'descricao_problema_saude.max' => 'A descrição do problema de saúde pode ter no máximo 2.000 caracteres.',
            'possui_deficiencia.required' => 'Informe se o membro possui deficiência.',
            'descricao_deficiencia.required' => 'Descreva a deficiência informada.',
            'descricao_deficiencia.max' => 'A descrição da deficiência pode ter no máximo 2.000 caracteres.',
            'observacoes.max' => 'As observações podem ter no máximo 5.000 caracteres.',
        ];
    }
}
