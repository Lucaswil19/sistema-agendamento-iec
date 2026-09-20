<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RelatorioAtendimentosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_inicio' => ['nullable', 'date_format:d/m/Y'],
            'data_fim' => ['nullable', 'date_format:d/m/Y'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'data_inicio.date_format' => 'Informe uma data inicial válida no formato dd/mm/aaaa.',
            'data_fim.date_format' => 'Informe uma data final válida no formato dd/mm/aaaa.',
            'page.integer' => 'A página informada é inválida.',
        ];
    }
}
