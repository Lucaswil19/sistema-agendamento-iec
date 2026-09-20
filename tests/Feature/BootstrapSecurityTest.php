<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BootstrapSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_cria_primeiro_lider_sem_receber_senha_por_argumento(): void
    {
        $this->artisan('app:criar-lider')
            ->expectsQuestion('Nome do líder', '  Líder Inicial  ')
            ->expectsQuestion('E-mail do líder', '  Lider.Inicial@Example.COM  ')
            ->expectsQuestion(
                'Senha (mínimo de 12 caracteres, com maiúscula, minúscula, número e símbolo)',
                'SenhaForte!123'
            )
            ->expectsQuestion('Confirme a senha', 'SenhaForte!123')
            ->expectsOutput('Primeiro líder criado com sucesso. A senha não foi exibida nem gravada em logs.')
            ->assertSuccessful();

        $lider = User::where('email', 'lider.inicial@example.com')->firstOrFail();

        $this->assertSame('Líder Inicial', $lider->name);
        $this->assertSame('lider', $lider->role);
        $this->assertTrue($lider->is_active);
        $this->assertTrue(Hash::check('SenhaForte!123', $lider->password));
    }

    public function test_comando_recusa_criar_outro_lider_quando_ja_existe_um_ativo(): void
    {
        User::factory()->create([
            'role' => 'lider',
            'is_active' => true,
        ]);

        $this->artisan('app:criar-lider')
            ->expectsOutput('Já existe um líder ativo. Cadastre os demais usuários pela interface do sistema.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_seeder_demo_exige_senha_forte_e_nao_redefine_conta_existente(): void
    {
        $this->definirSenhaDemo('SenhaDemoForte!123');

        $this->seed(DatabaseSeeder::class);

        $lider = User::where('email', 'lider@teste.com')->firstOrFail();
        $hashOriginal = $lider->password;

        $lider->is_active = false;
        $lider->save();
        $this->definirSenhaDemo('OutraSenhaForte!456');

        $this->seed(DatabaseSeeder::class);

        $lider->refresh();

        $this->assertFalse($lider->is_active);
        $this->assertSame($hashOriginal, $lider->password);
        $this->assertTrue(Hash::check('SenhaDemoForte!123', $lider->password));
    }

    protected function tearDown(): void
    {
        $this->definirSenhaDemo(null);

        parent::tearDown();
    }

    private function definirSenhaDemo(?string $password): void
    {
        if ($password === null) {
            putenv('DEMO_USER_PASSWORD');
            unset($_ENV['DEMO_USER_PASSWORD'], $_SERVER['DEMO_USER_PASSWORD']);

            return;
        }

        putenv("DEMO_USER_PASSWORD={$password}");
        $_ENV['DEMO_USER_PASSWORD'] = $password;
        $_SERVER['DEMO_USER_PASSWORD'] = $password;
    }
}
