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

class HistoricoFamiliarTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_nao_exclui_historico_familiar_de_outro_beneficiario(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $beneficiarioDaUrl = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '111.111.111-11',
            'email' => 'url@example.com',
        ]));

        $beneficiarioCorreto = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '222.222.222-22',
            'email' => 'correto@example.com',
        ]));

        $historico = Historico_familiar::forceCreate([
            'beneficiario_id' => $beneficiarioCorreto->id,
            'nome' => 'Dependente Teste',
            'data_nascimento' => '2010-01-01',
            'parentesco' => 'filho',
            'possui_problema_saude' => false,
            'possui_deficiencia' => false,
        ]);

        $response = $this
            ->actingAs($lider)
            ->delete(route('historico-familiar.destroy', [$beneficiarioDaUrl, $historico]));

        $response->assertNotFound();
        $this->assertDatabaseHas('historico_familiar', [
            'id' => $historico->id,
            'beneficiario_id' => $beneficiarioCorreto->id,
        ]);
    }

    public function test_cadastro_de_historico_recalcula_prioridade_de_agendamento_aberto(): void
    {
        Carbon::setTestNow('2026-07-09');

        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario([
            'cpf' => '333.333.333-33',
            'email' => 'prioridade@example.com',
        ]));

        $agendamento = Agendamento::forceCreate([
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

        $response = $this->actingAs($lider)->post(route('historico-familiar.store', $beneficiario), [
            'nome' => 'Familiar Prioritário',
            'data_nascimento' => '01/01/1950',
            'idade' => null,
            'parentesco' => 'mãe',
            'possui_problema_saude' => '1',
            'descricao_problema_saude' => 'Diabetes',
            'possui_deficiencia' => '1',
            'descricao_deficiencia' => 'Mobilidade reduzida',
            'observacoes' => null,
        ]);

        $response->assertRedirect(route('beneficiarios.show', $beneficiario));

        $agendamento->refresh();

        $this->assertSame('alta', $agendamento->prioridade);
        $this->assertSame(7, $agendamento->pontuacao_prioridade);
        $this->assertStringContainsString('idoso(s)', $agendamento->justificativa_prioridade);
        $this->assertStringContainsString('deficiência', $agendamento->justificativa_prioridade);
    }

    public function test_descricoes_medicas_do_familiar_sao_limpas_quando_indicador_muda_para_nao(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());
        $historico = Historico_familiar::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'nome' => 'Familiar',
            'data_nascimento' => '1990-01-01',
            'parentesco' => 'irmão',
            'possui_problema_saude' => true,
            'descricao_problema_saude' => 'Descrição antiga',
            'possui_deficiencia' => true,
            'descricao_deficiencia' => 'Descrição antiga',
        ]);

        $response = $this->actingAs($lider)->put(
            route('historico-familiar.update', [$beneficiario, $historico]),
            [
                'nome' => 'Familiar',
                'data_nascimento' => '01/01/1990',
                'idade' => '36',
                'parentesco' => 'irmão',
                'possui_problema_saude' => '0',
                'descricao_problema_saude' => 'Não deve permanecer',
                'possui_deficiencia' => '0',
                'descricao_deficiencia' => 'Não deve permanecer',
            ]
        );

        $response->assertRedirect(route('beneficiarios.show', $beneficiario));
        $historico->refresh();
        $this->assertFalse($historico->possui_problema_saude);
        $this->assertNull($historico->descricao_problema_saude);
        $this->assertFalse($historico->possui_deficiencia);
        $this->assertNull($historico->descricao_deficiencia);
    }

    public function test_falha_no_recalculo_desfaz_cadastro_familiar(): void
    {
        $lider = User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);
        $beneficiario = Beneficiario::forceCreate($this->dadosBeneficiario());

        $this->mock(PrioridadeService::class)
            ->shouldReceive('calcular')
            ->once()
            ->andThrow(new RuntimeException('Falha simulada no recálculo.'));
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($lider)->post(route('historico-familiar.store', $beneficiario), [
                'nome' => 'Cadastro que deve ser revertido',
                'data_nascimento' => '01/01/1990',
                'parentesco' => 'irmão',
                'possui_problema_saude' => '0',
                'possui_deficiencia' => '0',
            ]);

            $this->fail('A exceção simulada deveria ter sido lançada.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha simulada no recálculo.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('historico_familiar', [
            'nome' => 'Cadastro que deve ser revertido',
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
}
