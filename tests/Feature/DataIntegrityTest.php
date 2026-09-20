<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Beneficiario;
use App\Models\Historico_familiar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_novo_agendamento_sem_prioridade_explicita_inicia_como_baixa(): void
    {
        $usuario = $this->criarUsuario();
        $beneficiario = $this->criarBeneficiario();

        $id = DB::table('agendamento')->insertGetId([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $usuario->id,
            'responsavel_id' => $usuario->id,
            'tipo_acao' => 'Visita',
            'data_agendada' => '2026-08-01',
            'hora_agendada' => '10:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('agendamento', [
            'id' => $id,
            'prioridade' => 'baixa',
            'pontuacao_prioridade' => 0,
        ]);
    }

    public function test_banco_rejeita_agendamento_sem_responsavel(): void
    {
        $usuario = $this->criarUsuario();
        $beneficiario = $this->criarBeneficiario();

        $this->expectException(QueryException::class);

        DB::table('agendamento')->insert([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $usuario->id,
            'responsavel_id' => null,
            'tipo_acao' => 'Visita',
            'data_agendada' => '2026-08-01',
            'hora_agendada' => '10:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_migration_normaliza_cpfs_legados_para_onze_digitos(): void
    {
        $beneficiario = $this->criarBeneficiario();
        DB::table('beneficiarios')
            ->where('id', $beneficiario->id)
            ->update(['cpf' => '529.982.247-25']);

        $migration = require database_path(
            'migrations/2026_07_14_000004_normalize_beneficiary_cpf.php'
        );
        $migration->up();

        $this->assertDatabaseHas('beneficiarios', [
            'id' => $beneficiario->id,
            'cpf' => '52998224725',
        ]);
    }

    public function test_migration_recusa_cpf_legado_invalido_sem_alterar_o_registro(): void
    {
        $beneficiario = $this->criarBeneficiario();
        DB::table('beneficiarios')
            ->where('id', $beneficiario->id)
            ->update(['cpf' => '000.000.000-00']);

        $migration = require database_path(
            'migrations/2026_07_14_000004_normalize_beneficiary_cpf.php'
        );

        try {
            $migration->up();
            $this->fail('A migration deveria bloquear o CPF legado inválido.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('CPFs inválidos', $exception->getMessage());
        }

        $this->assertDatabaseHas('beneficiarios', [
            'id' => $beneficiario->id,
            'cpf' => '000.000.000-00',
        ]);
    }

    public function test_migration_de_idade_recusa_dados_invalidos_antes_de_normalizar_parentesco(): void
    {
        $beneficiario = $this->criarBeneficiario();
        $migration = require database_path(
            'migrations/2026_07_14_000001_use_birth_date_as_the_only_age_source.php'
        );
        $migration->down();

        $validoId = DB::table('historico_familiar')->insertGetId([
            'beneficiario_id' => $beneficiario->id,
            'nome' => 'Familiar válido',
            'data_nascimento' => '1990-01-01',
            'parentesco' => '  Irmã  ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invalidoId = DB::table('historico_familiar')->insertGetId([
            'beneficiario_id' => $beneficiario->id,
            'nome' => 'Familiar inválido',
            'data_nascimento' => null,
            'parentesco' => 'Filho',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $migration->up();
            $this->fail('A migration deveria bloquear o nascimento ausente.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('fonte de idade', $exception->getMessage());
        }

        $this->assertSame(
            '  Irmã  ',
            DB::table('historico_familiar')->where('id', $validoId)->value('parentesco')
        );

        DB::table('historico_familiar')->where('id', $invalidoId)->delete();
        $migration->up();

        $this->assertSame(
            'Irmã',
            DB::table('historico_familiar')->where('id', $validoId)->value('parentesco')
        );
    }

    public function test_backfill_preserva_nivel_legado_e_registra_pontuacao_coerente(): void
    {
        $migration = require database_path(
            'migrations/2026_07_14_000002_standardize_and_backfill_appointment_priority.php'
        );
        $migration->down();

        $usuario = $this->criarUsuario();
        $beneficiario = $this->criarBeneficiario();
        $agendamento = $this->criarAgendamento($beneficiario, $usuario, [
            'status' => 'completado',
            'prioridade' => 'alta',
            'pontuacao_prioridade' => 0,
            'justificativa_prioridade' => null,
        ]);

        $migration->up();
        $agendamento->refresh();

        $this->assertSame('alta', $agendamento->prioridade);
        $this->assertSame(6, $agendamento->pontuacao_prioridade);
        $this->assertStringContainsString(
            'Prioridade legada preservada',
            $agendamento->justificativa_prioridade
        );
    }

    public function test_comando_recalcula_somente_agendamentos_abertos_com_as_idades_atuais(): void
    {
        Carbon::setTestNow('2026-07-14 00:15:00');

        $usuario = $this->criarUsuario();
        $beneficiario = $this->criarBeneficiario();

        Historico_familiar::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'nome' => 'Filho que completou treze anos',
            'data_nascimento' => '2013-07-14',
            'parentesco' => 'Filho',
            'possui_problema_saude' => false,
            'possui_deficiencia' => false,
        ]);

        $aberto = $this->criarAgendamento($beneficiario, $usuario, [
            'status' => 'agendado',
            'prioridade' => 'media',
            'pontuacao_prioridade' => 3,
            'justificativa_prioridade' => 'Pontuação anterior ao aniversário.',
        ]);

        $encerrado = $this->criarAgendamento($beneficiario, $usuario, [
            'status' => 'completado',
            'prioridade' => 'urgente',
            'pontuacao_prioridade' => 9,
            'justificativa_prioridade' => 'Registro histórico encerrado.',
        ]);

        $this->artisan('prioridades:recalcular')
            ->expectsOutput('Prioridades recalculadas em 1 agendamento(s) aberto(s).')
            ->assertSuccessful();

        $aberto->refresh();
        $encerrado->refresh();

        $this->assertSame('baixa', $aberto->prioridade);
        $this->assertSame(0, $aberto->pontuacao_prioridade);
        $this->assertSame('urgente', $encerrado->prioridade);
        $this->assertSame(9, $encerrado->pontuacao_prioridade);
    }

    public function test_idade_familiar_e_derivada_somente_da_data_de_nascimento(): void
    {
        Carbon::setTestNow('2026-07-14');

        $beneficiario = $this->criarBeneficiario();
        $familiar = Historico_familiar::forceCreate([
            'beneficiario_id' => $beneficiario->id,
            'nome' => 'Familiar',
            'data_nascimento' => '2000-07-15',
            'parentesco' => 'Irmã',
            'possui_problema_saude' => false,
            'possui_deficiencia' => false,
        ]);

        $this->assertFalse(Schema::hasColumn('historico_familiar', 'idade'));
        $this->assertSame(25, $familiar->idade);
        $this->assertArrayNotHasKey('idade', $familiar->getAttributes());
    }

    public function test_banco_exige_data_de_nascimento_no_historico_familiar(): void
    {
        $beneficiario = $this->criarBeneficiario();

        $this->expectException(QueryException::class);

        DB::table('historico_familiar')->insert([
            'beneficiario_id' => $beneficiario->id,
            'nome' => 'Familiar sem nascimento',
            'parentesco' => 'Filho',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_rollback_de_numero_dependentes_reconstroi_a_contagem(): void
    {
        $beneficiario = $this->criarBeneficiario();

        foreach (['Filho', 'Filha'] as $indice => $nome) {
            Historico_familiar::forceCreate([
                'beneficiario_id' => $beneficiario->id,
                'nome' => $nome,
                'data_nascimento' => "201{$indice}-01-01",
                'parentesco' => $nome,
                'possui_problema_saude' => false,
                'possui_deficiencia' => false,
            ]);
        }

        $migration = require database_path(
            'migrations/2026_07_05_222237_remove_numero_dependentes_from_historico_familiar_table.php'
        );
        $migration->down();

        $this->assertSame(
            [2, 2],
            DB::table('historico_familiar')
                ->where('beneficiario_id', $beneficiario->id)
                ->orderBy('id')
                ->pluck('numero_dependentes')
                ->map(fn ($quantidade) => (int) $quantidade)
                ->all()
        );

        $migration->up();
    }

    public function test_banco_rejeita_historico_com_beneficiario_diferente_do_agendamento(): void
    {
        $usuario = $this->criarUsuario();
        $beneficiarioDoAgendamento = $this->criarBeneficiario();
        $outroBeneficiario = $this->criarBeneficiario([
            'cpf' => '987.654.321-00',
            'email' => 'outro@example.com',
        ]);
        $agendamento = $this->criarAgendamento($beneficiarioDoAgendamento, $usuario);

        $this->expectException(QueryException::class);

        DB::table('historico_acoes')->insert([
            'beneficiario_id' => $outroBeneficiario->id,
            'agendamento_id' => $agendamento->id,
            'user_id' => $usuario->id,
            'descricao' => 'Beneficiário divergente',
            'data_atendimento' => '2026-07-14',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_banco_impede_exclusao_destrutiva_de_beneficiario_com_agendamento(): void
    {
        $usuario = $this->criarUsuario();
        $beneficiario = $this->criarBeneficiario();
        $this->criarAgendamento($beneficiario, $usuario);

        $this->expectException(QueryException::class);

        DB::table('beneficiarios')->where('id', $beneficiario->id)->delete();
    }

    public function test_banco_permite_apenas_um_historico_por_agendamento(): void
    {
        $usuario = $this->criarUsuario();
        $beneficiario = $this->criarBeneficiario();
        $agendamento = $this->criarAgendamento($beneficiario, $usuario);

        $dados = [
            'beneficiario_id' => $beneficiario->id,
            'agendamento_id' => $agendamento->id,
            'user_id' => $usuario->id,
            'descricao' => 'Atendimento realizado',
            'data_atendimento' => '2026-07-14',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('historico_acoes')->insert($dados);

        $this->expectException(QueryException::class);

        DB::table('historico_acoes')->insert($dados);
    }

    public function test_banco_exige_agendamento_no_historico_de_acoes(): void
    {
        $usuario = $this->criarUsuario();
        $beneficiario = $this->criarBeneficiario();

        $this->expectException(QueryException::class);

        DB::table('historico_acoes')->insert([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $usuario->id,
            'descricao' => 'Atendimento sem agendamento',
            'data_atendimento' => '2026-07-14',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_agendamento_possui_um_unico_relacionamento_de_historico(): void
    {
        $agendamento = new Agendamento;

        $this->assertInstanceOf(HasOne::class, $agendamento->historicoAcoes());
    }

    private function criarUsuario(): User
    {
        return User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);
    }

    private function criarBeneficiario(array $sobrescrever = []): Beneficiario
    {
        static $sequencia = 0;
        $sequencia++;

        return Beneficiario::forceCreate(array_merge([
            'nome_beneficiario' => 'Beneficiário',
            'cpf' => sprintf('000.000.%03d-00', $sequencia),
            'telefone' => '(47) 99999-9999',
            'cep' => '88350000',
            'endereco' => 'Rua Teste, 1',
            'email' => "beneficiario{$sequencia}@example.com",
            'data_nascimento' => '1990-01-01',
            'possui_problema_saude' => false,
            'possui_deficiencia' => false,
            'is_active' => true,
        ], $sobrescrever));
    }

    private function criarAgendamento(
        Beneficiario $beneficiario,
        User $usuario,
        array $sobrescrever = []
    ): Agendamento {
        return Agendamento::forceCreate(array_merge([
            'beneficiario_id' => $beneficiario->id,
            'user_id' => $usuario->id,
            'responsavel_id' => $usuario->id,
            'tipo_acao' => 'Visita',
            'data_agendada' => '2026-08-01',
            'hora_agendada' => '10:00',
            'status' => 'agendado',
            'prioridade' => 'baixa',
            'pontuacao_prioridade' => 0,
            'justificativa_prioridade' => null,
        ], $sobrescrever));
    }
}
