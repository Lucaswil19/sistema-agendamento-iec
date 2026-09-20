<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Beneficiario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_nao_inativa_responsavel_com_agendamento_aberto(): void
    {
        $lider = $this->usuario('Líder', 'lider');
        $responsavel = $this->usuario('Voluntário', 'voluntario');
        $beneficiario = $this->beneficiario();

        Agendamento::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'responsavel_id' => $responsavel->id,
            'tipo_acao' => 'Visita',
            'data_agendada' => '2026-07-20',
            'hora_agendada' => '14:00',
            'status' => 'agendado',
            'prioridade' => 'baixa',
            'pontuacao_prioridade' => 0,
        ]);

        $response = $this->actingAs($lider)->patch(route('usuarios.inativar', $responsavel));

        $response->assertSessionHasErrors('usuario');
        $this->assertTrue($responsavel->fresh()->is_active);
    }

    public function test_alteracao_de_senha_revoga_sessoes_do_usuario_alterado(): void
    {
        config(['session.driver' => 'database']);

        $lider = $this->usuario('Líder', 'lider');
        $usuario = $this->usuario('Voluntário', 'voluntario');

        DB::table('sessions')->insert([
            [
                'id' => 'sessao-antiga-1',
                'user_id' => $usuario->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'payload',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'sessao-antiga-2',
                'user_id' => $usuario->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'payload',
                'last_activity' => now()->timestamp,
            ],
        ]);

        $response = $this->actingAs($lider)->put(route('usuarios.update', $usuario), [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'role' => $usuario->role,
            'password' => 'NovaSenha!Segura123',
            'password_confirmation' => 'NovaSenha!Segura123',
            'current_password' => 'password',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $this->assertSame(0, DB::table('sessions')->where('user_id', $usuario->id)->count());
        $this->assertTrue(Hash::check('NovaSenha!Segura123', $usuario->fresh()->password));
        $this->assertNotNull($usuario->fresh()->remember_token);
    }

    public function test_alteracao_de_senha_exige_senha_atual_do_lider(): void
    {
        $lider = $this->usuario('Líder', 'lider');
        $usuario = $this->usuario('Voluntário', 'voluntario');

        $response = $this->actingAs($lider)->put(route('usuarios.update', $usuario), [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'role' => $usuario->role,
            'password' => 'NovaSenha!Segura123',
            'password_confirmation' => 'NovaSenha!Segura123',
            'current_password' => 'senha-incorreta',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $usuario->fresh()->password));
    }

    public function test_lider_preserva_sessao_atual_ao_alterar_a_propria_senha(): void
    {
        config(['session.driver' => 'database']);

        $lider = $this->usuario('Líder', 'lider');
        $this->actingAs($lider);
        $staleSessionId = session()->getId();

        DB::table('sessions')->insert([
            'id' => $staleSessionId,
            'user_id' => $lider->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->put(route('usuarios.update', $lider), [
            'name' => $lider->name,
            'email' => $lider->email,
            'role' => $lider->role,
            'password' => 'NovaSenha!Segura123',
            'password_confirmation' => 'NovaSenha!Segura123',
            'current_password' => 'password',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $this->assertAuthenticatedAs($lider);
        $this->assertSame(1, DB::table('sessions')->where('user_id', $lider->id)->count());
        $this->assertDatabaseMissing('sessions', ['id' => $staleSessionId]);
    }

    public function test_inativacao_revoga_sessoes_e_token_persistente(): void
    {
        config(['session.driver' => 'database']);

        $lider = $this->usuario('Líder', 'lider');
        $usuario = $this->usuario('Voluntário', 'voluntario');
        $usuario->forceFill(['remember_token' => 'token-antigo'])->save();

        DB::table('sessions')->insert([
            'id' => 'sessao-do-inativado',
            'user_id' => $usuario->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($lider)->patch(route('usuarios.inativar', $usuario));

        $response->assertRedirect(route('usuarios.index'));
        $this->assertFalse($usuario->fresh()->is_active);
        $this->assertDatabaseMissing('sessions', [
            'id' => 'sessao-do-inativado',
        ]);
        $this->assertNotSame('token-antigo', $usuario->fresh()->remember_token);
    }

    public function test_politica_de_senha_rejeita_senha_fraca(): void
    {
        $lider = $this->usuario('Líder', 'lider');

        $response = $this->actingAs($lider)->post(route('usuarios.store'), [
            'name' => 'Novo usuário',
            'email' => 'novo@example.com',
            'role' => 'voluntario',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', [
            'email' => 'novo@example.com',
        ]);
    }

    public function test_filtros_de_usuario_rejeitam_arrays_malformados(): void
    {
        $lider = $this->usuario('Líder', 'lider');

        $response = $this->actingAs($lider)->get(route('usuarios.index', [
            'search' => ['texto'],
            'role' => ['lider'],
        ]));

        $response->assertSessionHasErrors(['search', 'role']);
    }

    public function test_ultimo_lider_ativo_nao_pode_mudar_de_perfil(): void
    {
        $lider = $this->usuario('Líder único', 'lider');

        $response = $this->actingAs($lider)->put(route('usuarios.update', $lider), [
            'name' => $lider->name,
            'email' => $lider->email,
            'role' => 'secretaria',
            'password' => null,
            'password_confirmation' => null,
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertSame('lider', $lider->fresh()->role);
    }

    public function test_remocoes_sucessivas_preservam_um_lider_ativo(): void
    {
        $primeiroLider = $this->usuario('Primeiro líder', 'lider');
        $segundoLider = $this->usuario('Segundo líder', 'lider');

        $this->actingAs($primeiroLider)
            ->put(route('usuarios.update', $segundoLider), [
                'name' => $segundoLider->name,
                'email' => $segundoLider->email,
                'role' => 'secretaria',
                'password' => null,
                'password_confirmation' => null,
            ])
            ->assertRedirect(route('usuarios.index'));

        $this->put(route('usuarios.update', $primeiroLider), [
            'name' => $primeiroLider->name,
            'email' => $primeiroLider->email,
            'role' => 'secretaria',
            'password' => null,
            'password_confirmation' => null,
        ])->assertSessionHasErrors('role');

        $this->assertSame(1, User::query()
            ->where('role', 'lider')
            ->where('is_active', true)
            ->count());
        $this->assertSame('lider', $primeiroLider->fresh()->role);
        $this->assertSame('secretaria', $segundoLider->fresh()->role);
    }

    private function usuario(string $nome, string $role): User
    {
        return User::factory()->create([
            'name' => $nome,
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function beneficiario(): Beneficiario
    {
        return Beneficiario::forceCreate([
            'nome_beneficiario' => 'Beneficiário Teste',
            'cpf' => '529.982.247-25',
            'telefone' => '+55 (47) 99999-9999',
            'cep' => '88350000',
            'endereco' => 'Rua Teste, 123',
            'email' => 'beneficiario@example.com',
            'data_nascimento' => '1990-01-01',
            'possui_problema_saude' => false,
            'possui_deficiencia' => false,
            'is_active' => true,
        ]);
    }
}
