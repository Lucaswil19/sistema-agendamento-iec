<?php

namespace App\Http\Requests;

use App\Models\Beneficiario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeneficiarioIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Beneficiario::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['ativo', 'inativo', 'todos'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'A busca deve ser um texto.',
            'search.max' => 'A busca pode ter no máximo 100 caracteres.',
            'status.in' => 'A situação informada é inválida.',
            'page.integer' => 'A página informada é inválida.',
        ];
    }
}
