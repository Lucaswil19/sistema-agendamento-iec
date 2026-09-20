<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Beneficiario;
use App\Models\User;
use App\Services\MaquinaEstadosAgendamento;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendamentoTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_nao_permite_responsavel_secretaria_ou_inativo_por_requisicao_manipulada(): void
    {
        Carbon::setTestNow('2026-07-13');

        $lider = $this->usuario('Líder', 'lider');
        $secretaria = $this->usuario('Secretária', 'secretaria');
        $voluntarioInativo = $this->usuario('Voluntário Inativo', 'voluntario', false);
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());

        $responseSecretaria = $this->actingAs($lider)->post(route('agendamento.store'), $this->dadosAgendamento([
            'beneficiario_id' => $beneficiario->id,
            'responsavel_id' => $secretaria->id,
        ]));

        $responseSecretaria->assertSessionHasErrors('responsavel_id');

        $responseInativo = $this->actingAs($lider)->post(route('agendamento.store'), $this->dadosAgendamento([
            'beneficiario_id' => $beneficiario->id,
            'responsavel_id' => $voluntarioInativo->id,
            'hora_agendada' => '16:00',
            'hora_final_agendada' => '17:00',
        ]));

        $responseInativo->assertSessionHasErrors('responsavel_id');
        $this->assertDatabaseCount('agendamento', 0);
    }

    public function test_responsavel_e_obrigatorio_na_criacao(): void
    {
        Carbon::setTestNow('2026-07-13');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());

        $response = $this->actingAs($lider)->post(route('agendamento.store'), $this->dadosAgendamento([
            'beneficiario_id' => $beneficiario->id,
            'responsavel_id' => null,
        ]));

        $response->assertSessionHasErrors('responsavel_id');
        $this->assertDatabaseCount('agendamento', 0);
    }

    public function test_edicao_nao_permite_remover_o_responsavel(): void
    {
        Carbon::setTestNow('2026-07-13');

        $lider = $this->usuario('Líder', 'lider');
        $responsavel = $this->usuario('Voluntário', 'voluntario');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'responsavel_id' => $responsavel->id,
        ]));

        $response = $this->actingAs($lider)->put(
            route('agendamento.update', $agendamento),
            $this->dadosAgendamento([
                'beneficiario_id' => $beneficiario->id,
                'responsavel_id' => null,
                'lock_version' => $agendamento->lock_version,
            ])
        );

        $response->assertSessionHasErrors('responsavel_id');
        $this->assertDatabaseHas('agendamento', [
            'id' => $agendamento->id,
            'responsavel_id' => $responsavel->id,
        ]);
    }

    public function test_nao_permite_beneficiario_inativo_por_requisicao_manipulada(): void
    {
        Carbon::setTestNow('2026-07-13');

        $lider = $this->usuario('Líder', 'lider');
        $responsavel = $this->usuario('Voluntário', 'voluntario');
        $beneficiarioInativo = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '111.111.111-11',
            'email' => 'inativo@example.com',
            'is_active' => false,
        ]));

        $response = $this->actingAs($lider)->post(route('agendamento.store'), $this->dadosAgendamento([
            'beneficiario_id' => $beneficiarioInativo->id,
            'responsavel_id' => $responsavel->id,
        ]));

        $response->assertSessionHasErrors('beneficiario_id');
        $this->assertDatabaseCount('agendamento', 0);
    }

    public function test_nao_permite_conflito_de_horario_para_mesmo_responsavel(): void
    {
        Carbon::setTestNow('2026-07-13');

        $lider = $this->usuario('Líder', 'lider');
        $responsavel = $this->usuario('Voluntário', 'voluntario');
        $beneficiarioExistente = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '222.222.222-22',
            'email' => 'existente@example.com',
        ]));
        $novoBeneficiario = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '333.333.333-33',
            'email' => 'novo@example.com',
        ]));

        Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiarioExistente->id,
            'user_id' => $lider->id,
            'responsavel_id' => $responsavel->id,
            'local' => 'Casa da família',
        ]));

        $response = $this->actingAs($lider)->post(route('agendamento.store'), $this->dadosAgendamento([
            'beneficiario_id' => $novoBeneficiario->id,
            'responsavel_id' => $responsavel->id,
            'data_agendada' => '20/07/2026',
            'hora_agendada' => '14:30',
            'hora_final_agendada' => '15:30',
            'local' => 'Outro local',
        ]));

        $response->assertSessionHasErrors('responsavel_id');
        $this->assertDatabaseCount('agendamento', 1);
    }

    public function test_nao_permite_conflito_de_horario_para_mesmo_local(): void
    {
        Carbon::setTestNow('2026-07-13');

        $lider = $this->usuario('Líder', 'lider');
        $responsavelExistente = $this->usuario('Voluntário Existente', 'voluntario');
        $novoResponsavel = $this->usuario('Voluntário Novo', 'voluntario');
        $beneficiarioExistente = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '444.444.444-44',
            'email' => 'local-existente@example.com',
        ]));
        $novoBeneficiario = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '555.555.555-55',
            'email' => 'local-novo@example.com',
        ]));

        Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiarioExistente->id,
            'user_id' => $lider->id,
            'responsavel_id' => $responsavelExistente->id,
            'local' => 'Igreja',
        ]));

        $response = $this->actingAs($lider)->post(route('agendamento.store'), $this->dadosAgendamento([
            'beneficiario_id' => $novoBeneficiario->id,
            'responsavel_id' => $novoResponsavel->id,
            'data_agendada' => '20/07/2026',
            'hora_agendada' => '14:30',
            'hora_final_agendada' => '15:30',
            'local' => 'igreja',
        ]));

        $response->assertSessionHasErrors('local');
        $this->assertDatabaseCount('agendamento', 1);
    }

    public function test_filtro_de_agendamentos_nao_aceita_data_inicial_posterior_a_final(): void
    {
        $lider = $this->usuario('Líder', 'lider');

        $response = $this->actingAs($lider)->get(route('agendamento.index', [
            'data_inicio' => '21/07/2026',
            'data_fim' => '20/07/2026',
        ]));

        $response->assertSessionHasErrors('data_inicio');
    }

    public function test_busca_de_agendamentos_aceita_cpf_formatado_com_armazenamento_canonico(): void
    {
        Carbon::setTestNow('2026-07-13 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario([
            'nome_beneficiario' => 'Pessoa localizada pelo CPF',
            'cpf' => '529.982.247-25',
        ]));
        Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
        ]));

        $response = $this->actingAs($lider)->get(route('agendamento.index', [
            'search' => '529.982.247-25',
        ]));

        $response->assertOk();
        $response->assertSee('Pessoa localizada pelo CPF');
        $this->assertDatabaseHas('beneficiarios', ['cpf' => '52998224725']);
    }

    public function test_colunas_da_listagem_abrem_os_detalhes_do_agendamento(): void
    {
        Carbon::setTestNow('2026-07-13 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
        ]));

        $response = $this->actingAs($lider)->get(route('agendamento.index'));

        $response->assertOk();
        $this->assertSame(
            7,
            substr_count($response->getContent(), 'data-detail-link="agendamento"')
        );
        $response->assertSee(route('agendamento.show', $agendamento), false);
    }

    public function test_criacao_serializa_operacoes_da_mesma_data(): void
    {
        Carbon::setTestNow('2026-07-13 10:00:00');
        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());

        $response = $this->actingAs($lider)->post(route('agendamento.store'), $this->dadosAgendamento([
            'beneficiario_id' => $beneficiario->id,
            'responsavel_id' => $lider->id,
        ]));

        $response->assertRedirect(route('agendamento.index'));
        $this->assertDatabaseHas('agendamento_data_locks', [
            'data_agendada' => '2026-07-20',
        ]);
        $this->assertDatabaseCount('agendamento', 1);
    }

    public function test_edicao_com_versao_obsoleta_nao_sobrescreve_alteracao_concorrente(): void
    {
        Carbon::setTestNow('2026-07-19 10:00:00');
        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'lock_version' => 1,
        ]));

        $response = $this->actingAs($lider)->put(
            route('agendamento.update', $agendamento),
            $this->dadosAgendamento([
                'beneficiario_id' => $beneficiario->id,
                'responsavel_id' => $agendamento->responsavel_id,
                'lock_version' => 0,
                'tipo_acao' => 'Tentativa obsoleta',
            ])
        );

        $response->assertSessionHasErrors('agendamento');
        $this->assertSame('Visita', $agendamento->fresh()->tipo_acao);
        $this->assertSame(1, $agendamento->fresh()->lock_version);
    }

    public function test_transicao_condicional_rejeita_estado_e_versao_alterados(): void
    {
        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
        ]));
        $snapshotObsoleto = $agendamento->fresh();

        Agendamento::query()
            ->whereKey($agendamento->getKey())
            ->update([
                'status' => 'cancelado',
                'lock_version' => 1,
            ]);

        $transicionou = app(MaquinaEstadosAgendamento::class)
            ->transicionarCondicional(
                $snapshotObsoleto,
                MaquinaEstadosAgendamento::COMPLETADO
            );

        $this->assertFalse($transicionou);
        $agendamento->refresh();
        $this->assertSame('cancelado', $agendamento->status);
        $this->assertSame(1, $agendamento->lock_version);
    }

    public function test_cancelamento_controlado_incrementa_versao_do_agendamento(): void
    {
        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
        ]));

        $response = $this->actingAs($lider)->patch(route('agendamento.cancelar', $agendamento), [
            'lock_version' => $agendamento->lock_version,
            'motivo_cancelamento' => 'Atendimento desmarcado pelo beneficiário.',
        ]);

        $response->assertRedirect(route('agendamento.historico'));
        $this->assertSame('cancelado', $agendamento->fresh()->status);
        $this->assertSame(1, $agendamento->fresh()->lock_version);
    }

    public function test_cancelamento_sem_motivo_e_permitido(): void
    {
        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
        ]));

        $response = $this->actingAs($lider)->patch(
            route('agendamento.cancelar', $agendamento),
            ['lock_version' => $agendamento->lock_version]
        );

        $response->assertRedirect(route('agendamento.historico'));
        $agendamento->refresh();
        $this->assertSame('cancelado', $agendamento->status);
        $this->assertNull($agendamento->motivo_cancelamento);
    }

    public function test_cancelamento_com_versao_obsoleta_nao_altera_agendamento(): void
    {
        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'lock_version' => 2,
        ]));

        $response = $this->actingAs($lider)->patch(
            route('agendamento.cancelar', $agendamento),
            [
                'lock_version' => 1,
                'motivo_cancelamento' => 'Tentativa obsoleta',
            ]
        );

        $response->assertSessionHasErrors('agendamento');
        $agendamento->refresh();
        $this->assertSame('agendado', $agendamento->status);
        $this->assertNull($agendamento->motivo_cancelamento);
        $this->assertSame(2, $agendamento->lock_version);
    }

    public function test_tela_de_edicao_lista_cada_responsavel_apenas_uma_vez(): void
    {
        Carbon::setTestNow('2026-07-13');

        $lider = $this->usuario('Líder', 'lider');
        $responsavel = $this->usuario('Voluntário Responsável', 'voluntario');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '666.666.666-66',
            'email' => 'editar@example.com',
        ]));

        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'responsavel_id' => $responsavel->id,
        ]));

        $response = $this->actingAs($lider)->get(route('agendamento.edit', $agendamento));

        $response->assertOk();
        $response->assertDontSee('name="status"', false);
        $this->assertSame(
            1,
            substr_count($response->getContent(), "<span>{$responsavel->name} - Voluntário</span>")
        );
    }

    public function test_voluntario_nao_acessa_detalhes_de_atendimento_alheio(): void
    {
        $lider = $this->usuario('Líder', 'lider');
        $responsavel = $this->usuario('Responsável', 'voluntario');
        $outroVoluntario = $this->usuario('Outro voluntário', 'voluntario');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'responsavel_id' => $responsavel->id,
        ]));

        $this->actingAs($outroVoluntario)
            ->get(route('agendamento.show', $agendamento))
            ->assertForbidden();
    }

    public function test_criacao_rejeita_horario_que_ja_passou_no_dia_atual(): void
    {
        Carbon::setTestNow('2026-07-20 15:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $responsavel = $this->usuario('Voluntário', 'voluntario');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());

        $response = $this->actingAs($lider)->post(route('agendamento.store'), $this->dadosAgendamento([
            'beneficiario_id' => $beneficiario->id,
            'responsavel_id' => $responsavel->id,
            'data_agendada' => '20/07/2026',
            'hora_agendada' => '14:00',
            'hora_final_agendada' => '14:30',
        ]));

        $response->assertSessionHasErrors('hora_agendada');
        $this->assertDatabaseCount('agendamento', 0);
    }

    public function test_agendamento_passado_pode_ser_marcado_como_perdido(): void
    {
        Carbon::setTestNow('2026-07-21 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $responsavel = $this->usuario('Voluntário', 'voluntario');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'responsavel_id' => $responsavel->id,
            'data_agendada' => '2026-07-20',
        ]));

        $response = $this->actingAs($lider)->patch(
            route('agendamento.marcar-como-perdido', $agendamento),
            ['lock_version' => $agendamento->lock_version]
        );

        $response->assertRedirect(route('agendamento.historico'));
        $this->assertDatabaseHas('agendamento', [
            'id' => $agendamento->id,
            'status' => 'perdido',
        ]);
    }

    public function test_agendamento_futuro_nao_pode_ser_marcado_como_perdido(): void
    {
        Carbon::setTestNow('2026-07-19 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
        ]));

        $response = $this->actingAs($lider)->patch(
            route('agendamento.marcar-como-perdido', $agendamento),
            ['lock_version' => $agendamento->lock_version]
        );

        $response->assertSessionHasErrors('status');
        $this->assertSame('agendado', $agendamento->fresh()->status);
    }

    public function test_agendamento_futuro_nao_vira_perdido_ao_manipular_a_data_no_mesmo_envio(): void
    {
        Carbon::setTestNow('2026-07-19 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
        ]));

        $response = $this->actingAs($lider)->patch(
            route('agendamento.marcar-como-perdido', $agendamento),
            [
                'lock_version' => $agendamento->lock_version,
                'data_agendada' => '18/07/2026',
            ]
        );

        $response->assertSessionHasErrors('status');
        $agendamento->refresh();
        $this->assertSame('agendado', $agendamento->status);
        $this->assertSame('2026-07-20', $agendamento->data_agendada->format('Y-m-d'));
    }

    public function test_agendamento_ja_perdido_nao_pode_ser_editado(): void
    {
        Carbon::setTestNow('2026-07-21 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'data_agendada' => '2026-07-20',
            'status' => 'perdido',
        ]));

        $this->actingAs($lider)
            ->get(route('agendamento.edit', $agendamento))
            ->assertForbidden();
    }

    public function test_edicao_aceita_manter_beneficiario_e_responsavel_atuais_inativos(): void
    {
        Carbon::setTestNow('2026-07-19 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $responsavel = $this->usuario('Voluntário inativo', 'voluntario', false);
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario([
            'is_active' => false,
        ]));
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'responsavel_id' => $responsavel->id,
        ]));

        $response = $this->actingAs($lider)->put(
            route('agendamento.update', $agendamento),
            $this->dadosAgendamento([
                'beneficiario_id' => $beneficiario->id,
                'responsavel_id' => $responsavel->id,
                'notas' => 'Vínculos atuais preservados.',
            ])
        );

        $response->assertRedirect(route('agendamento.index'));
        $this->assertSame('Vínculos atuais preservados.', $agendamento->fresh()->notas);
    }

    public function test_edicao_generica_rejeita_todos_os_status_enviados_manualmente(): void
    {
        Carbon::setTestNow('2026-07-19 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
        ]));

        foreach (['agendado', 'reagendado', 'completado', 'cancelado', 'perdido'] as $status) {
            $response = $this->actingAs($lider)->put(
                route('agendamento.update', $agendamento),
                $this->dadosAgendamento([
                    'beneficiario_id' => $beneficiario->id,
                    'responsavel_id' => $agendamento->responsavel_id,
                    'status' => $status,
                ])
            );

            $response->assertSessionHasErrors('status');
        }

        $this->assertSame('agendado', $agendamento->fresh()->status);
    }

    public function test_edicao_generica_nao_reabre_agendamentos_encerrados(): void
    {
        Carbon::setTestNow('2026-07-19 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());

        foreach (['completado', 'cancelado', 'perdido'] as $status) {
            $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
                'beneficiario_id' => $beneficiario->id,
                'user_id' => $lider->id,
                'status' => $status,
            ]));

            $this->actingAs($lider)->put(
                route('agendamento.update', $agendamento),
                $this->dadosAgendamento([
                    'beneficiario_id' => $beneficiario->id,
                    'responsavel_id' => $agendamento->responsavel_id,
                    'data_agendada' => '21/07/2026',
                ])
            )->assertForbidden();

            $this->assertSame($status, $agendamento->fresh()->status);
            $this->assertSame('2026-07-20', $agendamento->data_agendada->format('Y-m-d'));
        }
    }

    public function test_alteracao_de_data_pela_edicao_marca_agendamento_como_reagendado(): void
    {
        Carbon::setTestNow('2026-07-19 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
        ]));

        $response = $this->actingAs($lider)->put(
            route('agendamento.update', $agendamento),
            $this->dadosAgendamento([
                'beneficiario_id' => $beneficiario->id,
                'responsavel_id' => $agendamento->responsavel_id,
                'data_agendada' => '21/07/2026',
            ])
        );

        $response->assertRedirect(route('agendamento.index'));
        $agendamento->refresh();
        $this->assertSame('reagendado', $agendamento->status);
        $this->assertSame('2026-07-21', $agendamento->data_agendada->format('Y-m-d'));
        $this->assertSame(1, $agendamento->lock_version);
    }

    public function test_reabertura_de_cancelado_reagenda_e_remove_motivo_antigo(): void
    {
        Carbon::setTestNow('2026-07-20 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'status' => 'cancelado',
            'motivo_cancelamento' => 'Motivo antigo',
        ]));

        $response = $this->actingAs($lider)->patch(
            route('agendamento.reabrir', $agendamento),
            [
                'lock_version' => $agendamento->lock_version,
                'data_agendada' => '21/07/2026',
                'hora_agendada' => '09:00',
                'hora_final_agendada' => '10:00',
            ]
        );

        $response->assertRedirect(route('agendamento.show', $agendamento));
        $agendamento->refresh();
        $this->assertSame('reagendado', $agendamento->status);
        $this->assertNull($agendamento->motivo_cancelamento);
        $this->assertSame('2026-07-21', $agendamento->data_agendada->format('Y-m-d'));
        $this->assertSame('09:00', substr($agendamento->hora_agendada, 0, 5));
        $this->assertSame(1, $agendamento->lock_version);
    }

    public function test_agendamento_completado_nao_pode_ser_reaberto(): void
    {
        Carbon::setTestNow('2026-07-20 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'status' => 'completado',
        ]));

        $this->actingAs($lider)->patch(
            route('agendamento.reabrir', $agendamento),
            [
                'lock_version' => $agendamento->lock_version,
                'data_agendada' => '21/07/2026',
                'hora_agendada' => '09:00',
                'hora_final_agendada' => '10:00',
            ]
        )->assertForbidden();

        $this->assertSame('completado', $agendamento->fresh()->status);
    }

    public function test_endpoint_de_reabertura_nao_aceita_agendamento_ainda_aberto(): void
    {
        Carbon::setTestNow('2026-07-20 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'status' => 'agendado',
        ]));

        $this->actingAs($lider)->patch(
            route('agendamento.reabrir', $agendamento),
            [
                'lock_version' => $agendamento->lock_version,
                'data_agendada' => '21/07/2026',
                'hora_agendada' => '09:00',
                'hora_final_agendada' => '10:00',
            ]
        )->assertForbidden();

        $agendamento->refresh();
        $this->assertSame('agendado', $agendamento->status);
        $this->assertSame('2026-07-20', $agendamento->data_agendada->format('Y-m-d'));
        $this->assertSame(0, $agendamento->lock_version);
    }

    public function test_agendamento_perdido_pode_ser_reaberto_com_horario_futuro(): void
    {
        Carbon::setTestNow('2026-07-21 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'status' => 'perdido',
            'data_agendada' => '2026-07-20',
        ]));

        $this->actingAs($lider)->patch(
            route('agendamento.reabrir', $agendamento),
            [
                'lock_version' => $agendamento->lock_version,
                'data_agendada' => '22/07/2026',
                'hora_agendada' => '09:00',
                'hora_final_agendada' => '10:00',
            ]
        )->assertRedirect(route('agendamento.show', $agendamento));

        $agendamento->refresh();
        $this->assertSame('reagendado', $agendamento->status);
        $this->assertSame('2026-07-22', $agendamento->data_agendada->format('Y-m-d'));
    }

    public function test_conflito_na_reabertura_desfaz_toda_a_transacao(): void
    {
        Carbon::setTestNow('2026-07-20 10:00:00');

        $lider = $this->usuario('Líder', 'lider');
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'status' => 'cancelado',
            'motivo_cancelamento' => 'Motivo preservado em caso de erro',
        ]));
        Agendamento::forceCreate($this->agendamentoExistente([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'data_agendada' => '2026-07-21',
            'hora_agendada' => '09:00',
            'hora_final_agendada' => '10:00',
        ]));

        $response = $this->actingAs($lider)->patch(
            route('agendamento.reabrir', $agendamento),
            [
                'lock_version' => $agendamento->lock_version,
                'data_agendada' => '21/07/2026',
                'hora_agendada' => '09:00',
                'hora_final_agendada' => '10:00',
            ]
        );

        $response->assertSessionHasErrors('beneficiario_id');
        $agendamento->refresh();
        $this->assertSame('cancelado', $agendamento->status);
        $this->assertSame('Motivo preservado em caso de erro', $agendamento->motivo_cancelamento);
        $this->assertSame('2026-07-20', $agendamento->data_agendada->format('Y-m-d'));
        $this->assertSame(0, $agendamento->lock_version);
    }

    private function usuario(string $nome, string $role, bool $ativo = true): User
    {
        return User::factory()->create([
            'name' => $nome,
            'role' => $role,
            'is_active' => $ativo,
        ]);
    }

    private function dadosBeneficiario(array $sobrescrever = []): array
    {
        return array_merge([
            'nome_beneficiario' => 'Beneficiário Teste',
            'cpf' => '000.000.000-00',
            'telefone' => '+55 (47) 99999-9999',
            'cep' => '88350000',
            'endereco' => 'Rua Teste, 123',
            'email' => 'beneficiario@example.com',
            'data_nascimento' => '1990-01-01',
            'possui_problema_saude' => false,
            'descricao_problema_saude' => null,
            'possui_deficiencia' => false,
            'descricao_deficiencia' => null,
            'observacoes' => null,
            'is_active' => true,
        ], $sobrescrever);
    }

    private function dadosAgendamento(array $sobrescrever = []): array
    {
        return array_merge([
            'beneficiario_id' => null,
            'responsavel_id' => null,
            'lock_version' => 0,
            'tipo_acao' => 'Visita',
            'data_agendada' => '20/07/2026',
            'hora_agendada' => '14:00',
            'hora_final_agendada' => '15:00',
            'local' => 'Igreja',
            'notas' => null,
        ], $sobrescrever);
    }

    private function agendamentoExistente(array $sobrescrever = []): array
    {
        $dados = array_merge([
            'beneficiario_id' => null,
            'user_id' => null,
            'tipo_acao' => 'Visita',
            'data_agendada' => '2026-07-20',
            'hora_agendada' => '14:00',
            'hora_final_agendada' => '15:00',
            'status' => 'agendado',
            'lock_version' => 0,
            'prioridade' => 'baixa',
            'pontuacao_prioridade' => 0,
            'justificativa_prioridade' => null,
            'local' => 'Igreja',
            'notas' => null,
            'motivo_cancelamento' => null,
        ], $sobrescrever);

        $dados['responsavel_id'] ??= $dados['user_id'];

        return $dados;
    }
}
