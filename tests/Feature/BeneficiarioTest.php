<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Beneficiario;
use App\Models\Historico_familiar;
use App\Models\User;
use App\Services\PrioridadeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BeneficiarioTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_lider_consegue_cadastrar_beneficiario(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $response = $this->actingAs($lider)->post(route('beneficiarios.store'), [
            'nome_beneficiario' => 'Maria Silva',
            'cpf' => '123.456.789-09',
            'telefone' => '+55 (47) 99999-9999',
            'cep' => '88350-000',
            'endereco' => 'Rua Teste, 123',
            'email' => 'maria@example.com',
            'data_nascimento' => '10/05/1980',
            'possui_problema_saude' => '1',
            'descricao_problema_saude' => 'Hipertensão',
            'possui_deficiencia' => '0',
            'descricao_deficiencia' => null,
            'observacoes' => 'Cadastro criado pelo teste.',
        ]);

        $beneficiario = Beneficiario::where('cpf', '12345678909')->firstOrFail();

        $response->assertRedirect(route('beneficiarios.show', $beneficiario));
        $this->assertDatabaseHas('beneficiarios', [
            'cpf' => '12345678909',
            'email' => 'maria@example.com',
            'possui_problema_saude' => true,
            'possui_deficiencia' => false,
            'is_active' => true,
        ]);
        $this->assertSame('1980-05-10', $beneficiario->data_nascimento->format('Y-m-d'));
    }

    public function test_descricao_de_saude_e_obrigatoria_quando_problema_de_saude_for_informado(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $response = $this->actingAs($lider)->post(route('beneficiarios.store'), [
            'nome_beneficiario' => 'João Silva',
            'cpf' => '168.995.350-09',
            'telefone' => '+55 (47) 98888-7777',
            'cep' => '88350-000',
            'endereco' => 'Rua Validação, 456',
            'email' => 'joao@example.com',
            'data_nascimento' => '15/08/1992',
            'possui_problema_saude' => '1',
            'descricao_problema_saude' => null,
            'possui_deficiencia' => '0',
            'descricao_deficiencia' => null,
        ]);

        $response->assertSessionHasErrors('descricao_problema_saude');
        $this->assertDatabaseMissing('beneficiarios', [
            'cpf' => '168.995.350-09',
        ]);
    }

    public function test_atualizacao_de_beneficiario_recalcula_prioridade_de_agendamento_aberto(): void
    {
        Carbon::setTestNow('2026-07-13');

        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '529.982.247-25',
            'email' => 'recalculo@example.com',
        ]));

        $agendamentoAberto = Agendamento::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'responsavel_id' => $lider->id,
            'tipo_acao' => 'Visita',
            'data_agendada' => '2026-07-20',
            'hora_agendada' => '14:00',
            'hora_final_agendada' => null,
            'status' => 'agendado',
            'prioridade' => 'baixa',
            'pontuacao_prioridade' => 0,
            'justificativa_prioridade' => null,
        ]);

        $agendamentoFechado = Agendamento::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'responsavel_id' => $lider->id,
            'tipo_acao' => 'Atendimento finalizado',
            'data_agendada' => '2026-07-21',
            'hora_agendada' => '10:00',
            'hora_final_agendada' => null,
            'status' => 'completado',
            'prioridade' => 'baixa',
            'pontuacao_prioridade' => 0,
            'justificativa_prioridade' => null,
        ]);

        $response = $this->actingAs($lider)->put(route('beneficiarios.update', $beneficiario), [
            'nome_beneficiario' => 'Beneficiário Prioritário',
            'cpf' => '529.982.247-25',
            'telefone' => '+55 (47) 99999-9999',
            'cep' => '88350-000',
            'endereco' => 'Rua Teste, 123',
            'email' => 'recalculo@example.com',
            'data_nascimento' => '01/01/1950',
            'possui_problema_saude' => '1',
            'descricao_problema_saude' => 'Hipertensão',
            'possui_deficiencia' => '1',
            'descricao_deficiencia' => 'Mobilidade reduzida',
            'observacoes' => 'Atualizado pelo teste.',
        ]);

        $response->assertRedirect(route('beneficiarios.show', $beneficiario));

        $agendamentoAberto->refresh();
        $agendamentoFechado->refresh();

        $this->assertSame('alta', $agendamentoAberto->prioridade);
        $this->assertSame(7, $agendamentoAberto->pontuacao_prioridade);
        $this->assertSame('baixa', $agendamentoFechado->prioridade);
        $this->assertSame(0, $agendamentoFechado->pontuacao_prioridade);
    }

    public function test_detalhes_do_beneficiario_nao_repetem_campos_e_classificam_12_anos_como_crianca(): void
    {
        Carbon::setTestNow('2026-07-13');

        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '222.333.444-55',
            'email' => 'detalhes@example.com',
            'observacoes' => 'Observação única.',
        ]));

        Historico_familiar::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'nome' => 'Dependente de 12 anos',
            'data_nascimento' => '2014-07-13',
            'parentesco' => 'filho',
            'possui_problema_saude' => false,
            'possui_deficiencia' => false,
        ]);

        $response = $this->actingAs($lider)->get(route('beneficiarios.show', $beneficiario));

        $response->assertOk();
        $response->assertSee('Criança');

        $conteudo = $response->getContent();

        $this->assertSame(1, substr_count($conteudo, 'data-beneficiario-field="data-nascimento"'));
        $this->assertSame(1, substr_count($conteudo, 'data-beneficiario-field="observacoes"'));
    }

    public function test_botao_de_adicionar_historico_some_apos_o_primeiro_cadastro_familiar(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());

        $semHistorico = $this->actingAs($lider)
            ->get(route('beneficiarios.show', $beneficiario));

        $semHistorico->assertOk();
        $semHistorico->assertSeeText('Adicionar histórico familiar');

        Historico_familiar::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'nome' => 'Primeiro familiar',
            'data_nascimento' => '2010-01-01',
            'parentesco' => 'filho',
            'possui_problema_saude' => false,
            'possui_deficiencia' => false,
        ]);

        $comHistorico = $this->actingAs($lider)
            ->get(route('beneficiarios.show', $beneficiario));

        $comHistorico->assertOk();
        $comHistorico->assertDontSeeText('Adicionar histórico familiar');
        $comHistorico->assertSeeText('Adicionar membro familiar');
    }

    public function test_informacoes_do_cartao_abrem_os_detalhes_do_beneficiario(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());

        $response = $this->actingAs($lider)->get(route('home'));

        $response->assertOk();
        $response->assertSee('data-detail-link="beneficiario"', false);
        $response->assertSee(route('beneficiarios.show', $beneficiario), false);
    }

    public function test_colunas_de_agendamento_na_ficha_abrem_os_detalhes_do_agendamento(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $agendamento = Agendamento::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'responsavel_id' => $lider->id,
            'tipo_acao' => 'Visita',
            'data_agendada' => '2026-08-10',
            'hora_agendada' => '14:00',
            'hora_final_agendada' => '15:00',
            'status' => 'agendado',
            'prioridade' => 'baixa',
            'pontuacao_prioridade' => 0,
        ]);

        $response = $this->actingAs($lider)
            ->get(route('beneficiarios.show', $beneficiario));

        $response->assertOk();
        $this->assertSame(
            4,
            substr_count($response->getContent(), 'data-detail-link="agendamento"')
        );
        $response->assertSee(route('agendamento.show', $agendamento), false);
    }

    public function test_cpf_sem_mascara_e_normalizado_e_digitos_invalidos_sao_rejeitados(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $dados = [
            'nome_beneficiario' => 'CPF Normalizado',
            'cpf' => '11144477735',
            'telefone' => '+55 (47) 99999-9999',
            'cep' => '88350000',
            'endereco' => 'Rua Teste, 123',
            'email' => 'cpf@example.com',
            'data_nascimento' => '01/01/1990',
            'possui_problema_saude' => '0',
            'possui_deficiencia' => '0',
        ];

        $response = $this->actingAs($lider)->post(route('beneficiarios.store'), $dados);

        $response->assertRedirect();
        $this->assertDatabaseHas('beneficiarios', [
            'cpf' => '11144477735',
        ]);

        $dados['cpf'] = '000.000.000-00';
        $dados['email'] = 'cpf-invalido@example.com';

        $response = $this->actingAs($lider)->post(route('beneficiarios.store'), $dados);

        $response->assertSessionHasErrors('cpf');
        $this->assertDatabaseMissing('beneficiarios', [
            'email' => 'cpf-invalido@example.com',
        ]);
    }

    public function test_descricoes_medicas_sao_limpas_quando_indicador_muda_para_nao(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '529.982.247-25',
            'possui_problema_saude' => true,
            'descricao_problema_saude' => 'Descrição antiga',
            'possui_deficiencia' => true,
            'descricao_deficiencia' => 'Descrição antiga',
        ]));

        $response = $this->actingAs($lider)->put(
            route('beneficiarios.update', $beneficiario),
            [
                'nome_beneficiario' => $beneficiario->nome_beneficiario,
                'cpf' => $beneficiario->cpf,
                'telefone' => $beneficiario->telefone,
                'cep' => $beneficiario->cep,
                'endereco' => $beneficiario->endereco,
                'email' => $beneficiario->email,
                'data_nascimento' => '01/01/1990',
                'possui_problema_saude' => '0',
                'descricao_problema_saude' => 'Não deve permanecer',
                'possui_deficiencia' => '0',
                'descricao_deficiencia' => 'Não deve permanecer',
            ]
        );

        $response->assertRedirect(route('beneficiarios.show', $beneficiario));
        $beneficiario->refresh();
        $this->assertFalse($beneficiario->possui_problema_saude);
        $this->assertNull($beneficiario->descricao_problema_saude);
        $this->assertFalse($beneficiario->possui_deficiencia);
        $this->assertNull($beneficiario->descricao_deficiencia);
    }

    public function test_beneficiario_inativo_pode_ser_filtrado_e_reativado(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario([
            'is_active' => false,
        ]));

        $responsePadrao = $this->actingAs($lider)->get(route('home'));
        $responsePadrao->assertViewHas('beneficiarios', function ($paginador) use ($beneficiario) {
            return ! $paginador->getCollection()->contains('id', $beneficiario->id);
        });

        $responseInativos = $this->actingAs($lider)->get(route('home', ['status' => 'inativo']));
        $responseInativos->assertViewHas('beneficiarios', function ($paginador) use ($beneficiario) {
            return $paginador->getCollection()->contains('id', $beneficiario->id);
        });

        $response = $this->actingAs($lider)->patch(route('beneficiarios.reativar', $beneficiario));

        $response->assertRedirect(route('beneficiarios.show', $beneficiario));
        $this->assertTrue($beneficiario->fresh()->is_active);
    }

    public function test_inativacao_preserva_cadastro_mesmo_sem_vinculos(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());

        $response = $this->actingAs($lider)->patch(route('beneficiarios.inativar', $beneficiario));

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('beneficiarios', [
            'id' => $beneficiario->id,
            'is_active' => false,
        ]);
    }

    public function test_detalhes_entregam_relacoes_paginadas(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());

        for ($indice = 1; $indice <= 17; $indice++) {
            Historico_familiar::forceCreate([
                'beneficiario_id' => $beneficiario->id,
                'nome' => "Familiar {$indice}",
                'data_nascimento' => '2010-01-01',
                'parentesco' => 'filho',
                'possui_problema_saude' => false,
                'possui_deficiencia' => false,
            ]);
        }

        $response = $this->actingAs($lider)->get(route('beneficiarios.show', $beneficiario));

        $response->assertOk();
        $response->assertViewHas('historicoFamiliar', function ($paginador) {
            return $paginador->count() === 15 && $paginador->total() === 17;
        });
        $response->assertViewHasAll(['agendamentos', 'historicoAcoes']);
    }

    public function test_detalhes_rejeitam_paginadores_malformados(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());

        $response = $this->actingAs($lider)->get(route('beneficiarios.show', [
            'beneficiario' => $beneficiario,
            'familia_page' => ['invalida'],
            'agendamentos_page' => ['invalida'],
            'acoes_page' => ['invalida'],
        ]));

        $response->assertSessionHasErrors([
            'familia_page',
            'agendamentos_page',
            'acoes_page',
        ]);
    }

    public function test_falha_no_recalculo_desfaz_atualizacao_do_beneficiario(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '529.982.247-25',
        ]));

        $this->mock(PrioridadeService::class)
            ->shouldReceive('calcular')
            ->once()
            ->andThrow(new RuntimeException('Falha simulada no recálculo.'));
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($lider)->put(route('beneficiarios.update', $beneficiario), [
                'nome_beneficiario' => 'Nome que deve ser revertido',
                'cpf' => $beneficiario->cpf,
                'telefone' => $beneficiario->telefone,
                'cep' => $beneficiario->cep,
                'endereco' => $beneficiario->endereco,
                'email' => $beneficiario->email,
                'data_nascimento' => '01/01/1990',
                'possui_problema_saude' => '0',
                'possui_deficiencia' => '0',
            ]);

            $this->fail('A exceção simulada deveria ter sido lançada.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha simulada no recálculo.', $exception->getMessage());
        }

        $this->assertSame('Beneficiário Teste', $beneficiario->fresh()->nome_beneficiario);
    }

    private function dadosBeneficiario(array $sobrescrever = []): array
    {
        return array_merge([
            'nome_beneficiario' => 'Beneficiário Teste',
            'cpf' => '529.982.247-25',
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
}
