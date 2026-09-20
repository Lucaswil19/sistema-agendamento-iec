<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendamentoIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'data_inicio' => ['nullable', 'date_format:d/m/Y'],
            'data_fim' => ['nullable', 'date_format:d/m/Y'],
            'status' => [
                'nullable',
                Rule::in(['agendado', 'completado', 'cancelado', 'perdido', 'reagendado']),
            ],
            'prioridade' => [
                'nullable',
                Rule::in(['baixa', 'media', 'alta', 'urgente']),
            ],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'pendencias_page' => [
            'nullable',
            'integer',
            'min:1',
            'max:1000000',
                            ],

            'historico_page' => [
            'nullable',
            'integer',
            'min:1',
            'max:1000000',
                            ],
        ];
        
        

    }

    public function messages(): array
    {
        return [
            'search.string' => 'A busca deve ser um texto.',
            'search.max' => 'A busca pode ter no máximo 100 caracteres.',
            'data_inicio.date_format' => 'Informe uma data inicial válida no formato dd/mm/aaaa.',
            'data_fim.date_format' => 'Informe uma data final válida no formato dd/mm/aaaa.',
            'status.in' => 'O status informado é inválido.',
            'prioridade.in' => 'A prioridade informada é inválida.',
            'page.integer' => 'A página informada é inválida.',
            'page.min' => 'A página informada é inválida.',
            'page.max' => 'A página informada é inválida.',
            'pendencias_page.integer' =>'A página de pendências informada é inválida.',
            'pendencias_page.min' =>'A página de pendências informada é inválida.',
            'pendencias_page.max' => 'A página de pendências informada é inválida.',
            'historico_page.integer' =>'A página do histórico informada é inválida.',
            'historico_page.min' => 'A página do histórico informada é inválida.',
            'historico_page.max' =>'A página do histórico informada é inválida.',
        ];
    }
}
