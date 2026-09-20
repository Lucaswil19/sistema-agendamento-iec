<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RedefinirSenhaRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => [
                'required',
                'string',
                'max:255',
                Password::defaults(),
                'confirmed',
            ],
            'password_confirmation' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'O link de recuperação é inválido ou expirou. Solicite um novo link.',
            'token.string' => 'O link de recuperação é inválido ou expirou. Solicite um novo link.',
            'email.required' => 'Informe o e-mail.',
            'email.string' => 'O e-mail informado é inválido.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve possuir no máximo 255 caracteres.',
            'password.required' => 'Informe a nova senha.',
            'password.string' => 'A nova senha informada é inválida.',
            'password.max' => 'A nova senha deve possuir no máximo 255 caracteres.',
            'password.min' => 'A nova senha deve possuir pelo menos 12 caracteres.',
            'password.mixed' => 'A nova senha deve conter letras maiúsculas e minúsculas.',
            'password.numbers' => 'A nova senha deve conter pelo menos um número.',
            'password.symbols' => 'A nova senha deve conter pelo menos um símbolo.',
            'password.confirmed' => 'A confirmação da nova senha não corresponde.',
            'password_confirmation.required' => 'Confirme a nova senha.',
            'password_confirmation.string' => 'A confirmação da nova senha é inválida.',
            'password_confirmation.max' => 'A confirmação da nova senha deve possuir no máximo 255 caracteres.',
        ];
    }
}
