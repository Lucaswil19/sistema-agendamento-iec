<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Beneficiario;
use App\Models\Historico_acoes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RelatorioTest extends TestCase
{
    use RefreshDatabase;

    public function test_relatorio_geral_nao_aceita_data_invalida(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $response = $this->actingAs($lider)->get(route('relatorios.atendimentos', [
            'data_inicio' => '99/99/9999',
        ]));

        $response->assertSessionHasErrors('data_inicio');
    }

    public function test_relatorio_geral_nao_aceita_data_inicial_posterior_a_final(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $response = $this->actingAs($lider)->get(route('relatorios.atendimentos', [
            'data_inicio' => '21/07/2026',
            'data_fim' => '20/07/2026',
        ]));

        $response->assertSessionHasErrors('data_inicio');
    }

    public function test_relatorio_filtra_data_realizada_e_expoe_metricas_semanticas(): void
    {
        $lider = $this->lider();
        $beneficiario = $this->beneficiario();
        $urgente = $this->agendamento($lider, $beneficiario, [
            'data_agendada' => '2026-07-01',
            'prioridade' => 'urgente',
        ]);
        $comum = $this->agendamento($lider, $beneficiario, [
            'data_agendada' => '2026-07-10',
            'prioridade' => 'baixa',
        ]);

        $incluido = Historico_acoes::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'agendamento_id' => $urgente->id,
            'user_id' => $lider->id,
            'descricao' => 'Atendimento incluído pelo dia realizado.',
            'tipo_atendimento' => 'Visita',
            'data_atendimento' => '2026-07-10',
            'encaminhamentos' => 'Encaminhado à rede pública.',
        ]);
        Historico_acoes::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'agendamento_id' => $comum->id,
            'user_id' => $lider->id,
            'descricao' => 'Atendimento fora do filtro pela data realizada.',
            'tipo_atendimento' => 'Entrega',
            'data_atendimento' => '2026-07-11',
        ]);

        $response = $this->actingAs($lider)->get(route('relatorios.atendimentos', [
            'data_inicio' => '10/07/2026',
            'data_fim' => '10/07/2026',
        ]));

        $response->assertOk();
        $response->assertViewHas('totalAtendimentos', 1);
        $response->assertViewHas('totalBeneficiarios', 1);
        $response->assertViewHas('totalUrgentes', 1);
        $response->assertViewHas('totalComEncaminhamento', 1);
        $response->assertViewHas('atendimentos', function ($paginador) use ($incluido) {
            return $paginador->perPage() === 20
                && $paginador->total() === 1
                && $paginador->first()->is($incluido);
        });
        $response->assertSee(route('agendamento.show', $urgente), false);
    }

    public function test_relatorio_rejeita_pagina_malformada(): void
    {
        $lider = $this->lider();

        $response = $this->actingAs($lider)->get(route('relatorios.atendimentos', [
            'page' => ['invalida'],
        ]));

        $response->assertSessionHasErrors('page');
    }

    public function test_totais_consideram_todas_as_paginas_sem_repetir_contagens(): void
    {
        $lider = $this->lider();
        $beneficiario = $this->beneficiario();

        for ($i = 0; $i < 26; $i++) {
            $agendamento = $this->agendamento($lider, $beneficiario, [
                'prioridade' => $i % 2 === 0 ? 'urgente' : 'baixa',
            ]);
            Historico_acoes::forceCreate([
                'beneficiario_id' => $beneficiario->id,
                'agendamento_id' => $agendamento->id,
                'user_id' => $lider->id,
                'descricao' => 'Atendimento de teste',
                'data_atendimento' => '2026-07-10',
                'encaminhamentos' => $i % 3 === 0 ? 'Rede de apoio' : ($i % 3 === 1 ? '   ' : null),
            ]);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->actingAs($lider)->get(route('relatorios.atendimentos', ['page' => 2]));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk()
            ->assertViewHas('totalAtendimentos', 26)
            ->assertViewHas('totalBeneficiarios', 1)
            ->assertViewHas('totalUrgentes', 13)
            ->assertViewHas('totalComEncaminhamento', 9)
            ->assertViewHas('atendimentos', fn ($page) => $page->count() === 6 && $page->total() === 26);
        $this->assertLessThanOrEqual(6, count($queries), 'O relatorio repetiu consultas ou carregou relacoes por linha.');
    }

    public function test_relatorio_vazio_mantem_totais_zerados(): void
    {
        $this->actingAs($this->lider())->get(route('relatorios.atendimentos'))
            ->assertOk()
            ->assertViewHas('totalAtendimentos', 0)
            ->assertViewHas('totalBeneficiarios', 0)
            ->assertViewHas('totalUrgentes', 0)
            ->assertViewHas('totalComEncaminhamento', 0);
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
            'data_agendada' => '2026-07-01',
            'hora_agendada' => '14:00',
            'status' => 'completado',
            'prioridade' => 'baixa',
            'pontuacao_prioridade' => 0,
        ], $sobrescrever));
    }
}
