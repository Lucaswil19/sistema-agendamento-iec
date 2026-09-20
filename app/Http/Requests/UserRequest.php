<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
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
        $user = $this->route('user');
        $regrasSenha = [
            $this->isMethod('POST') ? 'required' : 'nullable',
            'string',
            'max:255',
            Password::defaults(),
            'confirmed',
        ];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->getKey()),
            ],
            'role' => ['required', Rule::in(['lider', 'secretaria', 'voluntario'])],
            'password' => $regrasSenha,
            'current_password' => [
                Rule::excludeIf($this->isMethod('POST') || blank($this->input('password'))),
                Rule::requiredIf(! $this->isMethod('POST') && filled($this->input('password'))),
                'nullable',
                'string',
                'max:255',
                'current_password:web',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do usuário.',
            'email.required' => 'Informe o e-mail do usuário.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'role.required' => 'Selecione o perfil do usuário.',
            'role.in' => 'O perfil selecionado é inválido.',
            'password.required' => 'Informe uma senha.',
            'password.min' => 'A senha deve possuir pelo menos 12 caracteres.',
            'password.mixed' => 'A senha deve conter letras maiúsculas e minúsculas.',
            'password.numbers' => 'A senha deve conter pelo menos um número.',
            'password.symbols' => 'A senha deve conter pelo menos um símbolo.',
            'password.max' => 'A senha pode ter no máximo 255 caracteres.',
            'password.confirmed' => 'A confirmação da senha não corresponde.',
            'current_password.required' => 'Confirme sua senha atual para alterar a senha deste usuário.',
            'current_password.current_password' => 'A senha atual informada está incorreta.',
        ];
    }
}
