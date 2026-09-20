<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AtualizarSenhaPerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_pode_acessar_o_perfil(): void
    {
        $this->get(route('perfil.edit'))
            ->assertRedirect(route('login'));

        $this->put(route('perfil.senha.update'))
            ->assertRedirect(route('login'));
    }

    public function test_todos_os_perfis_podem_acessar_o_proprio_perfil(): void
    {
        foreach (['lider', 'secretaria', 'voluntario'] as $role) {
            $usuario = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($usuario)
                ->get(route('perfil.edit'))
                ->assertOk();
        }
    }

    public function test_senha_atual_incorreta_impede_a_alteracao(): void
    {
        $usuario = User::factory()->create([
            'role' => 'secretaria',
            'is_active' => true,
        ]);

        $response = $this->actingAs($usuario)
            ->from(route('perfil.edit'))
            ->put(route('perfil.senha.update'), [
                'current_password' => 'senha-incorreta',
                'password' => 'NovaSenha!Segura123',
                'password_confirmation' => 'NovaSenha!Segura123',
            ]);

        $response->assertRedirect(route('perfil.edit'));
        $response->assertSessionHasErrors('current_password');

        $this->assertTrue(
            Hash::check(
                'password',
                $usuario->fresh()->password
            )
        );
    }

    public function test_confirmacao_incorreta_impede_a_alteracao(): void
    {
        $usuario = User::factory()->create([
            'role' => 'voluntario',
            'is_active' => true,
        ]);

        $response = $this->actingAs($usuario)
            ->from(route('perfil.edit'))
            ->put(route('perfil.senha.update'), [
                'current_password' => 'password',
                'password' => 'NovaSenha!Segura123',
                'password_confirmation' => 'OutraSenha!Segura123',
            ]);

        $response->assertRedirect(route('perfil.edit'));
        $response->assertSessionHasErrors('password');

        $this->assertTrue(
            Hash::check(
                'password',
                $usuario->fresh()->password
            )
        );
    }

    public function test_usuario_altera_senha_e_mantem_sessao_atual(): void
    {
        config(['session.driver' => 'database']);

        $usuario = User::factory()->create([
            'role' => 'voluntario',
            'is_active' => true,
            'remember_token' => 'token-antigo',
        ]);

        $this->actingAs($usuario);

        $sessaoAtual = session()->getId();

        DB::table('sessions')->insert([
            [
                'id' => $sessaoAtual,
                'user_id' => $usuario->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'payload',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'outra-sessao',
                'user_id' => $usuario->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'payload',
                'last_activity' => now()->timestamp,
            ],
        ]);

        $response = $this->put(
            route('perfil.senha.update'),
            [
                'current_password' => 'password',
                'password' => 'NovaSenha!Segura123',
                'password_confirmation' => 'NovaSenha!Segura123',
            ]
        );

        $response->assertRedirect(route('perfil.edit'));
        $response->assertSessionHas('success');

        $this->assertAuthenticatedAs($usuario);

        $this->assertTrue(
            Hash::check(
                'NovaSenha!Segura123',
                $usuario->fresh()->password
            )
        );

        $this->assertNotSame(
            'token-antigo',
            $usuario->fresh()->remember_token
        );

        $this->assertDatabaseMissing('sessions', [
            'id' => 'outra-sessao',
        ]);
    }
}