<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('O DemoUserSeeder só pode ser executado nos ambientes local e testing.');
        }

        $password = env('DEMO_USER_PASSWORD');

        $validator = Validator::make(
            ['password' => $password],
            [
                'password' => [
                    'required',
                    'string',
                    Password::defaults(),
                ],
            ]
        );

        if ($validator->fails()) {
            throw new RuntimeException(
                'Defina DEMO_USER_PASSWORD com ao menos 12 caracteres, maiúscula, minúscula, número e símbolo.'
            );
        }

        foreach ([
            ['name' => 'Usuário Líder', 'email' => 'lider@teste.com', 'role' => 'lider'],
            ['name' => 'Usuária Secretária', 'email' => 'secretaria@teste.com', 'role' => 'secretaria'],
            ['name' => 'Usuário Voluntário', 'email' => 'voluntario@teste.com', 'role' => 'voluntario'],
        ] as $demoUser) {
            if (User::query()->where('email', $demoUser['email'])->exists()) {
                continue;
            }

            $user = new User([
                'name' => $demoUser['name'],
                'email' => $demoUser['email'],
                'password' => Hash::make($password),
            ]);
            $user->role = $demoUser['role'];
            $user->is_active = true;
            $user->save();
        }
    }
}
