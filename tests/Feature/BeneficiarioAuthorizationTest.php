<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Beneficiario;
use App\Models\Historico_acoes;
use App\Models\Historico_familiar;
use App\Models\User;
use App\Policies\BeneficiarioPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class BeneficiarioAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_voluntario_lista_somente_beneficiarios_com_atendimento_atribuido(): void
    {
        [$voluntario, $outroVoluntario, $lider] = $this->usuarios();
        $atribuido = $this->beneficiario('Atribuído', '529.982.247-25', 'atribuido@example.com');
        $naoAtribuido = $this->beneficiario('Não atribuído', '111.444.777-35', 'nao-atribuido@example.com');
        $this->agendamento($atribuido, $lider, $voluntario, 'Meu atendimento');
        $this->agendamento($naoAtribuido, $lider, $outroVoluntario, 'Atendimento alheio');

        $response = $this->actingAs($voluntario)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Atribuído');
        $response->assertDontSee('Não atribuído');
    }

    public function test_voluntario_nao_acessa_beneficiario_sem_vinculo(): void
    {
        [$voluntario, $outroVoluntario, $lider] = $this->usuarios();
        $beneficiario = $this->beneficiario('Sem vínculo', '529.982.247-25', 'sem-vinculo@example.com');
        $this->agendamento($beneficiario, $lider, $outroVoluntario, 'Atendimento alheio');

        $this->actingAs($voluntario)
            ->get(route('beneficiarios.show', $beneficiario))
            ->assertForbidden();
    }

    public function test_policy_autoriza_perfis_amplos_e_voluntario_somente_com_vinculo(): void
    {
        [$voluntario, $outroVoluntario, $lider] = $this->usuarios();
        $secretaria = User::factory()->create(['role' => 'secretaria', 'is_active' => true]);
        $beneficiario = $this->beneficiario('Protegido pela policy', '529.982.247-25', 'policy@example.com');
        $this->agendamento($beneficiario, $lider, $voluntario, 'Atendimento vinculado');

        $this->assertInstanceOf(BeneficiarioPolicy::class, Gate::getPolicyFor($beneficiario));
        $this->assertTrue(Gate::forUser($lider)->allows('view', $beneficiario));
        $this->assertTrue(Gate::forUser($secretaria)->allows('view', $beneficiario));
        $this->assertTrue(Gate::forUser($voluntario)->allows('view', $beneficiario));
        $this->assertFalse(Gate::forUser($outroVoluntario)->allows('view', $beneficiario));
    }

    public function test_regra_a_libera_ficha_e_historico_familiar_por_vinculo_antigo(): void
    {
        [$voluntario, , $lider] = $this->usuarios();
        $beneficiario = $this->beneficiario('Regra A', '529.982.247-25', 'regra-a@example.com');
        $this->agendamento($beneficiario, $lider, $voluntario, 'Atendimento concluido');

        Historico_familiar::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'nome' => 'Familiar autorizado',
            'data_nascimento' => '2010-01-01',
            'parentesco' => 'filho',
            'possui_problema_saude' => true,
            'descricao_problema_saude' => 'Informacao familiar permitida pela regra A',
            'possui_deficiencia' => false,
        ]);

        $response = $this->actingAs($voluntario)
            ->get(route('beneficiarios.show', $beneficiario));

        $response->assertOk();
        $response->assertSee('Familiar autorizado');
        $response->assertSee('Informacao familiar permitida pela regra A');
    }

    public function test_ficha_para_voluntario_oculta_agenda_e_relatorios_de_outros_responsaveis(): void
    {
        [$voluntario, $outroVoluntario, $lider] = $this->usuarios();
        $beneficiario = $this->beneficiario('Compartilhado', '529.982.247-25', 'compartilhado@example.com');
        $meu = $this->agendamento($beneficiario, $lider, $voluntario, 'Meu atendimento');
        $alheio = $this->agendamento($beneficiario, $lider, $outroVoluntario, 'Atendimento alheio');
        $this->historico($meu, $voluntario, 'Meu relatório');
        $this->historico($alheio, $outroVoluntario, 'Relatório alheio');

        $response = $this->actingAs($voluntario)
            ->get(route('beneficiarios.show', $beneficiario));

        $response->assertOk();
        $response->assertSee('Meu atendimento');
        $response->assertSee('Meu relatório');
        $response->assertDontSee('Atendimento alheio');
        $response->assertDontSee('Relatório alheio');
    }

    private function usuarios(): array
    {
        return [
            User::factory()->create(['role' => 'voluntario', 'is_active' => true]),
            User::factory()->create(['role' => 'voluntario', 'is_active' => true]),
            User::factory()->create(['role' => 'lider', 'is_active' => true]),
        ];
    }

    private function beneficiario(string $nome, string $cpf, string $email): Beneficiario
    {
        return Beneficiario::forceCreate([
            'nome_beneficiario' => $nome,
            'cpf' => $cpf,
            'telefone' => '+55 (47) 99999-9999',
            'cep' => '88350000',
            'endereco' => 'Rua Teste, 123',
            'email' => $email,
            'data_nascimento' => '1990-01-01',
            'possui_problema_saude' => false,
            'possui_deficiencia' => false,
            'is_active' => true,
        ]);
    }

    private function agendamento(
        Beneficiario $beneficiario,
        User $lider,
        User $responsavel,
        string $tipo
    ): Agendamento {
        return Agendamento::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $lider->id,
            'responsavel_id' => $responsavel->id,
            'tipo_acao' => $tipo,
            'data_agendada' => '2026-07-01',
            'hora_agendada' => '14:00',
            'status' => 'completado',
            'prioridade' => 'baixa',
            'pontuacao_prioridade' => 0,
        ]);
    }

    private function historico(Agendamento $agendamento, User $autor, string $descricao): Historico_acoes
    {
        return Historico_acoes::forceCreate([
            'beneficiario_id' => $agendamento->beneficiario_id,
            'agendamento_id' => $agendamento->id,
            'user_id' => $autor->id,
            'descricao' => $descricao,
            'tipo_atendimento' => $agendamento->tipo_acao,
            'data_atendimento' => '2026-07-01',
        ]);
    }
}
