<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AtualizarSenhaPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                'max:255',
                'current_password:web',
            ],
            'password' => [
                'required',
                'string',
                'max:255',
                Password::defaults(),
                'confirmed',
                'different:current_password',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' =>
                'Informe sua senha atual.',

            'current_password.current_password' =>
                'A senha atual informada está incorreta.',

            'password.required' =>
                'Informe a nova senha.',

            'password.min' =>
                'A nova senha deve possuir pelo menos 12 caracteres.',

            'password.mixed' =>
                'A nova senha deve conter letras maiúsculas e minúsculas.',

            'password.numbers' =>
                'A nova senha deve conter pelo menos um número.',

            'password.symbols' =>
                'A nova senha deve conter pelo menos um símbolo.',

            'password.confirmed' =>
                'A confirmação da nova senha não corresponde.',

            'password.different' =>
                'A nova senha deve ser diferente da senha atual.',
        ];
    }
}