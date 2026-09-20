<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Beneficiario;
use App\Models\Historico_acoes;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricoAcoesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_relatorio_nao_pode_ser_aberto_antes_do_horario_agendado(): void
    {
        Carbon::setTestNow('2026-07-20 13:59:00');
        [$lider, $agendamento] = $this->cenarioAgendado();

        $response = $this->actingAs($lider)->get(route('historico-acoes.create', $agendamento));

        $response->assertForbidden();
        $this->assertDatabaseCount('historico_acoes', 0);
    }

    public function test_relatorio_conclui_atendimento_depois_do_horario_agendado(): void
    {
        Carbon::setTestNow('2026-07-20 14:01:00');
        [$lider, $agendamento] = $this->cenarioAgendado();

        $response = $this->actingAs($lider)->post(
            route('historico-acoes.store', $agendamento),
            $this->dadosRelatorio()
        );

        $response->assertRedirect(route('agendamento.show', $agendamento));
        $agendamento->refresh();
        $this->assertSame('completado', $agendamento->status);
        $this->assertSame(1, $agendamento->lock_version);
        $this->assertDatabaseHas('historico_acoes', [
            'agendamento_id' => $agendamento->id,
            'beneficiario_id' => $agendamento->beneficiario_id,
            'data_atendimento' => '2026-07-20 00:00:00',
        ]);

        $this->get(route('agendamento.show', $agendamento))
            ->assertOk()
            ->assertSee('Relatório de finalização')
            ->assertSee('Atendimento realizado conforme planejado.')
            ->assertSee($lider->name);
    }

    public function test_relatorio_sem_tipo_de_atendimento_armazena_null_e_conclui_agendamento(): void
    {
        Carbon::setTestNow('2026-07-20 14:01:00');
        [$lider, $agendamento] = $this->cenarioAgendado();
        $dados = $this->dadosRelatorio();
        unset($dados['tipo_atendimento']);

        $response = $this->actingAs($lider)->post(
            route('historico-acoes.store', $agendamento),
            $dados
        );

        $response->assertRedirect(route('agendamento.show', $agendamento));
        $this->assertSame('completado', $agendamento->fresh()->status);
        $this->assertDatabaseHas('historico_acoes', [
            'agendamento_id' => $agendamento->id,
            'tipo_atendimento' => null,
        ]);
    }

    public function test_data_realizada_nao_pode_anteceder_data_agendada(): void
    {
        Carbon::setTestNow('2026-07-21 10:00:00');
        [$lider, $agendamento] = $this->cenarioAgendado();

        $response = $this->actingAs($lider)->post(
            route('historico-acoes.store', $agendamento),
            $this->dadosRelatorio([
                'data_atendimento' => '19/07/2026',
            ])
        );

        $response->assertSessionHasErrors('data_atendimento');
        $this->assertSame('agendado', $agendamento->fresh()->status);
        $this->assertDatabaseCount('historico_acoes', 0);
    }

    public function test_nao_cria_segundo_relatorio_para_o_mesmo_agendamento(): void
    {
        Carbon::setTestNow('2026-07-21 10:00:00');
        [$lider, $agendamento] = $this->cenarioAgendado();

        Historico_acoes::forceCreate([
            'beneficiario_id' => $agendamento->beneficiario_id,
            'agendamento_id' => $agendamento->id,
            'user_id' => $lider->id,
            'descricao' => 'Relatório já existente.',
            'tipo_atendimento' => 'Visita',
            'data_atendimento' => '2026-07-20',
        ]);

        $response = $this->actingAs($lider)->post(
            route('historico-acoes.store', $agendamento),
            $this->dadosRelatorio()
        );

        $response->assertSessionHasErrors('agendamento');
        $this->assertDatabaseCount('historico_acoes', 1);
    }

    public function test_cancelamento_vencedor_impede_conclusao_concorrente(): void
    {
        Carbon::setTestNow('2026-07-21 10:00:00');
        [$lider, $agendamento] = $this->cenarioAgendado();
        $agendamento->refresh();

        $this->actingAs($lider)
            ->patch(route('agendamento.cancelar', $agendamento), [
                'lock_version' => $agendamento->lock_version,
            ])
            ->assertRedirect(route('agendamento.historico'));

        $this->post(
            route('historico-acoes.store', $agendamento),
            $this->dadosRelatorio()
        )->assertForbidden();

        $this->assertSame('cancelado', $agendamento->fresh()->status);
        $this->assertDatabaseCount('historico_acoes', 0);
    }

    public function test_conclusao_vencedora_impede_cancelamento_concorrente(): void
    {
        Carbon::setTestNow('2026-07-21 10:00:00');
        [$lider, $agendamento] = $this->cenarioAgendado();
        $agendamento->refresh();
        $versaoInicial = $agendamento->lock_version;

        $this->actingAs($lider)
            ->post(
                route('historico-acoes.store', $agendamento),
                $this->dadosRelatorio()
            )
            ->assertRedirect(route('agendamento.show', $agendamento));

        $this->patch(route('agendamento.cancelar', $agendamento), [
            'lock_version' => $versaoInicial,
        ])->assertSessionHasErrors('agendamento');

        $agendamento->refresh();
        $this->assertSame('completado', $agendamento->status);
        $this->assertSame(1, $agendamento->lock_version);
        $this->assertDatabaseCount('historico_acoes', 1);
    }

    public function test_voluntario_nao_registra_relatorio_de_atendimento_alheio(): void
    {
        Carbon::setTestNow('2026-07-21 10:00:00');
        [, $agendamento] = $this->cenarioAgendado();
        $voluntario = User::factory()->create([
            'role' => 'voluntario',
            'is_active' => true,
        ]);

        $this->actingAs($voluntario)
            ->post(route('historico-acoes.store', $agendamento), $this->dadosRelatorio())
            ->assertForbidden();

        $this->assertDatabaseCount('historico_acoes', 0);
        $this->assertSame('agendado', $agendamento->fresh()->status);
    }

    private function cenarioAgendado(): array
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);
        $beneficiario = Beneficiario::forceCreate([
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
        $agendamento = Agendamento::forceCreate([
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
        ]);

        return [$lider, $agendamento];
    }

    private function dadosRelatorio(array $sobrescrever = []): array
    {
        return array_merge([
            'descricao' => 'Atendimento realizado conforme planejado.',
            'tipo_atendimento' => 'Visita',
            'data_atendimento' => '20/07/2026',
            'encaminhamentos' => null,
            'observacoes' => null,
        ], $sobrescrever);
    }
}
