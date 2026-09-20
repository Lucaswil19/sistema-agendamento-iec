<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Beneficiario;
use App\Models\Historico_familiar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_login_publico_e_logout_autenticado_sao_acessiveis(): void
    {
        $this->get(route('login'))->assertOk();

        $lider = $this->lider();

        $this->actingAs($lider)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_lider_renderiza_formularios_e_detalhes_principais(): void
    {
        Carbon::setTestNow('2026-07-14 12:00:00');
        $lider = $this->lider();
        $usuario = User::factory()->create([
            'role' => 'voluntario',
            'is_active' => true,
        ]);
        $beneficiario = $this->beneficiario();
        $familiar = Historico_familiar::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'nome' => 'Familiar para edição',
            'data_nascimento' => '2010-01-01',
            'parentesco' => 'filho',
            'possui_problema_saude' => false,
            'possui_deficiencia' => false,
        ]);
        $agendamento = $this->agendamento($lider, $beneficiario, [
            'responsavel_id' => $usuario->id,
            'data_agendada' => '2026-07-20',
        ]);
        $agendamentoOcorrido = $this->agendamento($lider, $beneficiario, [
            'data_agendada' => '2026-07-13',
            'hora_agendada' => '10:00',
            'hora_final_agendada' => '11:00',
        ]);

        $rotas = [
            route('beneficiarios.create'),
            route('beneficiarios.edit', $beneficiario),
            route('historico-familiar.create', $beneficiario),
            route('historico-familiar.edit', [$beneficiario, $familiar]),
            route('agendamento.create'),
            route('agendamento.show', $agendamento),
            route('usuarios.index'),
            route('usuarios.create'),
            route('usuarios.edit', $usuario),
            route('historico-acoes.create', $agendamentoOcorrido),
        ];

        foreach ($rotas as $rota) {
            $this->actingAs($lider)->get($rota)->assertOk();
        }
    }

    public function test_ciclo_valido_de_usuario_inclui_cadastro_reativacao_e_exclusao(): void
    {
        $lider = $this->lider();

        $this->actingAs($lider)->post(route('usuarios.store'), [
            'name' => 'Novo Voluntário',
            'email' => '  Novo.Voluntario@Example.COM ',
            'role' => 'voluntario',
            'password' => 'SenhaForte!123',
            'password_confirmation' => 'SenhaForte!123',
        ])->assertRedirect(route('usuarios.index'));

        $cadastrado = User::where('email', 'novo.voluntario@example.com')->firstOrFail();
        $this->assertTrue($cadastrado->is_active);

        $inativo = User::factory()->create([
            'role' => 'secretaria',
            'is_active' => false,
        ]);

        $this->actingAs($lider)
            ->patch(route('usuarios.reativar', $inativo))
            ->assertRedirect(route('usuarios.index'));
        $inativo->refresh();
        $this->assertTrue($inativo->is_active);

        $inativo->is_active = false;
        $inativo->save();

        $this->actingAs($lider)
            ->delete(route('usuarios.destroy', $inativo))
            ->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseMissing('users', ['id' => $inativo->id]);
    }

    public function test_exclusao_valida_de_familiar_recalcula_e_remove_o_registro(): void
    {
        $lider = $this->lider();
        $beneficiario = $this->beneficiario();
        $familiar = Historico_familiar::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'nome' => 'Familiar removível',
            'data_nascimento' => '2010-01-01',
            'parentesco' => 'filho',
            'possui_problema_saude' => false,
            'possui_deficiencia' => false,
        ]);

        $this->actingAs($lider)
            ->delete(route('historico-familiar.destroy', [$beneficiario, $familiar]))
            ->assertRedirect(route('beneficiarios.show', $beneficiario));

        $this->assertDatabaseMissing('historico_familiar', ['id' => $familiar->id]);
    }

    public function test_listagem_de_usuarios_pagina_e_preserva_filtro(): void
    {
        $lider = $this->lider();

        User::factory()->count(12)->create([
            'role' => 'voluntario',
            'is_active' => true,
        ]);

        $response = $this->actingAs($lider)->get(route('usuarios.index', [
            'role' => 'voluntario',
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertViewHas('usuarios', function ($paginador) {
            return $paginador->currentPage() === 2
                && $paginador->total() === 12
                && str_contains($paginador->url(1), 'role=voluntario');
        });
    }

    private function lider(): User
    {
        return User::factory()->create([
            'role' => 'lider',
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

    private function agendamento(User $lider, Beneficiario $beneficiario, array $sobrescrever = []): Agendamento
    {
        return Agendamento::forceCreate(array_merge([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'responsavel_id' => $lider->id,
            'tipo_acao' => 'Visita',
            'data_agendada' => '2026-07-20',
            'hora_agendada' => '14:00',
            'hora_final_agendada' => '15:00',
            'status' => 'agendado',
            'prioridade' => 'baixa',
            'pontuacao_prioridade' => 0,
        ], $sobrescrever));
    }
}
