<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['lider', 'secretaria', 'voluntario'])],
            'status' => ['nullable', Rule::in(['ativo', 'inativo'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'A busca deve ser um texto.',
            'search.max' => 'A busca pode ter no máximo 100 caracteres.',
            'role.in' => 'O perfil informado é inválido.',
            'status.in' => 'A situação informada é inválida.',
            'page.integer' => 'A página informada é inválida.',
        ];
    }
}
