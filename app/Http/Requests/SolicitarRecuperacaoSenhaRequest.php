<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitarRecuperacaoSenhaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge([
                'email' => mb_strtolower(trim($this->input('email'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail.',
            'email.string' => 'O e-mail informado é inválido.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve possuir no máximo 255 caracteres.',
        ];
    }
}
