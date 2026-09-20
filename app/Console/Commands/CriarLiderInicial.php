<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CriarLiderInicial extends Command
{
    protected $signature = 'app:criar-lider';

    protected $description = 'Cria o primeiro usuário líder de forma interativa e segura';

    public function handle(): int
    {
        if (User::where('role', 'lider')->where('is_active', true)->exists()) {
            $this->error('Já existe um líder ativo. Cadastre os demais usuários pela interface do sistema.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Nome do líder'));
        $email = mb_strtolower(trim((string) $this->ask('E-mail do líder')));
        $password = $this->secret('Senha (mínimo de 12 caracteres, com maiúscula, minúscula, número e símbolo)');
        $passwordConfirmation = $this->secret('Confirme a senha');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    Password::defaults(),
                ],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->name = $validator->validated()['name'];
        $user->email = $validator->validated()['email'];
        $user->password = $validator->validated()['password'];
        $user->role = 'lider';
        $user->is_active = true;
        $user->save();

        $this->info('Primeiro líder criado com sucesso. A senha não foi exibida nem gravada em logs.');

        return self::SUCCESS;
    }
}
