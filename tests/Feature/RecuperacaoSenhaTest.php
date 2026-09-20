<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RedefinirSenhaNotification;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class RecuperacaoSenhaTest extends TestCase
{
    use RefreshDatabase;

    private const CURRENT_PASSWORD = 'SenhaAtual!1234';

    private const NEW_PASSWORD = 'NovaSenha!5678';

    private const GENERIC_RESPONSE = 'Caso exista uma conta ativa associada ao e-mail informado, as instruções para redefinição da senha serão enviadas.';

    private const INVALID_LINK = 'O link de recuperação é inválido ou expirou. Solicite um novo link.';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config(['session.driver' => 'database']);
    }

    public function test_visitante_visualiza_esqueci_minha_senha(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Esqueci minha senha')
            ->assertSee('Enviar link de recuperação')
            ->assertSee('lang="pt-BR"', false);
    }

    public function test_link_de_recuperacao_aparece_na_tela_de_login(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Esqueceu sua senha?')
            ->assertSee('href="'.route('password.request').'"', false);
    }

    public function test_usuario_autenticado_nao_acessa_rotas_de_recuperacao(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->actingAs($user)
            ->get(route('password.request'))
            ->assertRedirect('/');

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect('/');

        $this->get($this->resetUrl($token, $user->email))
            ->assertRedirect('/');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect('/');
    }

    public function test_email_da_solicitacao_e_normalizado(): void
    {
        $user = $this->user(['email' => 'usuario@example.com']);

        $this->post(route('password.email'), [
            'email' => '  USUARIO@EXAMPLE.COM  ',
        ])->assertSessionHas('status', self::GENERIC_RESPONSE)
            ->assertSessionHasInput('email', 'usuario@example.com');

        Notification::assertSentTo($user, RedefinirSenhaNotification::class);
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'usuario@example.com',
        ]);
    }

    public function test_email_malformado_e_rejeitado(): void
    {
        $this->post(route('password.email'), [
            'email' => 'email-invalido',
        ])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_usuario_ativo_solicita_recuperacao_com_resposta_generica(): void
    {
        $user = $this->user();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', self::GENERIC_RESPONSE);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_throttle_do_broker_mantem_a_resposta_generica(): void
    {
        $user = $this->user();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', self::GENERIC_RESPONSE);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', self::GENERIC_RESPONSE);

        Notification::assertSentToTimes(
            $user,
            RedefinirSenhaNotification::class,
            1
        );
    }

    public function test_falha_do_servico_mantem_resposta_generica_e_log_sanitizado(): void
    {
        $user = $this->user();
        Log::spy();
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with([
                'email' => $user->email,
                'is_active' => true,
            ])
            ->andThrow(new RuntimeException('token e URL sensíveis'));

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', self::GENERIC_RESPONSE);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Falha ao processar o envio de recuperação de senha.');
        Notification::assertNothingSent();
    }

    public function test_notificacao_correta_e_preparada_para_usuario_ativo(): void
    {
        $user = $this->user([
            'name' => 'Usuário de Teste',
            'email' => 'usuario@example.com',
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo(
            $user,
            RedefinirSenhaNotification::class,
            function (RedefinirSenhaNotification $notification, array $channels) use ($user): bool {
                $mail = $notification->toMail($user);
                $content = implode(' ', [
                    ...$mail->introLines,
                    ...$mail->outroLines,
                ]);

                return $channels === ['mail']
                    && $mail->subject === 'Redefinição de senha — Sistema de Agendamento IEC'
                    && $mail->actionText === 'Redefinir senha'
                    && $mail->actionUrl === route('password.reset', [
                        'token' => $notification->token,
                        'email' => $user->email,
                    ])
                    && str_contains($content, '60 minutos')
                    && str_contains($content, 'uma única vez')
                    && str_contains($content, 'ignore esta mensagem')
                    && ! str_contains($content, self::CURRENT_PASSWORD)
                    && ! str_contains(mb_strtolower($content), 'beneficiário');
            }
        );
    }

    public function test_usuario_inexistente_recebe_a_mesma_resposta_generica(): void
    {
        $this->post(route('password.email'), [
            'email' => 'inexistente@example.com',
        ])->assertSessionHas('status', self::GENERIC_RESPONSE);
    }

    public function test_usuario_inexistente_nao_recebe_notificacao(): void
    {
        $this->post(route('password.email'), [
            'email' => 'inexistente@example.com',
        ]);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_usuario_inativo_recebe_a_mesma_resposta_generica(): void
    {
        $user = $this->user(['is_active' => false]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', self::GENERIC_RESPONSE);
    }

    public function test_usuario_inativo_nao_recebe_notificacao(): void
    {
        $user = $this->user(['is_active' => false]);

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_throttle_http_limita_solicitacoes_excessivas(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.77']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('password.email'), [
                'email' => "inexistente{$attempt}@example.com",
            ])->assertSessionHas('status', self::GENERIC_RESPONSE);
        }

        $this->post(route('password.email'), [
            'email' => 'bloqueado@example.com',
        ])->assertTooManyRequests();
    }

    public function test_token_valido_exibe_tela_de_redefinicao(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->get($this->resetUrl($token, $user->email))
            ->assertOk()
            ->assertSee('Redefinir senha')
            ->assertSee('value="'.$token.'"', false)
            ->assertSee('value="'.$user->email.'"', false);
    }

    public function test_token_valido_redefine_a_senha_e_dispara_evento(): void
    {
        Event::fake([PasswordReset::class]);
        $user = $this->user();
        $token = Password::createToken($user);

        $this->postReset($user, $token)
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check(
            self::NEW_PASSWORD,
            $user->fresh()->password
        ));
        Event::assertDispatched(PasswordReset::class);
    }

    public function test_token_invalido_e_rejeitado_com_mensagem_generica(): void
    {
        $user = $this->user();

        $this->postReset($user, 'token-invalido')
            ->assertSessionHasErrors([
                'email' => self::INVALID_LINK,
            ]);

        $this->assertTrue(Hash::check(
            self::CURRENT_PASSWORD,
            $user->fresh()->password
        ));
    }

    public function test_token_expirado_e_rejeitado(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subMinutes(61)]);

        $this->postReset($user, $token)
            ->assertSessionHasErrors([
                'email' => self::INVALID_LINK,
            ]);

        $this->assertTrue(Hash::check(
            self::CURRENT_PASSWORD,
            $user->fresh()->password
        ));
    }

    public function test_token_ja_utilizado_nao_pode_ser_reutilizado(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->postReset($user, $token)
            ->assertRedirect(route('login'));

        $secondPassword = 'OutraSenha!9012';

        $this->postReset($user, $token, $secondPassword)
            ->assertSessionHasErrors([
                'email' => self::INVALID_LINK,
            ]);

        $this->assertTrue(Hash::check(
            self::NEW_PASSWORD,
            $user->fresh()->password
        ));
    }

    public function test_usuario_inativado_apos_receber_token_nao_redefine_senha(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);
        $user->is_active = false;
        $user->save();

        $this->postReset($user, $token)
            ->assertSessionHasErrors([
                'email' => self::INVALID_LINK,
            ]);

        $this->assertTrue(Hash::check(
            self::CURRENT_PASSWORD,
            $user->fresh()->password
        ));
    }

    public function test_senha_fora_da_politica_atual_e_rejeitada(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->postReset($user, $token, 'senha-fraca')
            ->assertSessionHasErrors('password')
            ->assertSessionHas('_old_input', fn (array $input): bool => ! array_key_exists('password', $input))
            ->assertSessionHasInput('email', $user->email);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_confirmacao_divergente_e_rejeitada_sem_reapresentar_senhas(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->postReset(
            $user,
            $token,
            self::NEW_PASSWORD,
            'SenhaDiferente!9012'
        )->assertSessionHasErrors('password')
            ->assertSessionHas(
                '_old_input',
                fn (array $input): bool => ! array_key_exists('password', $input)
                    && ! array_key_exists('password_confirmation', $input)
            )
            ->assertSessionHasInput('email', $user->email);
    }

    public function test_reutilizacao_da_senha_atual_e_rejeitada_apos_validar_token(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->postReset($user, $token, self::CURRENT_PASSWORD)
            ->assertSessionHasErrors([
                'password' => 'A nova senha deve ser diferente da senha anterior.',
            ]);

        $this->assertTrue(Hash::check(
            self::CURRENT_PASSWORD,
            $user->fresh()->password
        ));
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_senha_antiga_deixa_de_autenticar_e_nova_passa_a_autenticar(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);
        $this->postReset($user, $token);

        $this->post(route('login.autenticar'), [
            'email' => $user->email,
            'password' => self::CURRENT_PASSWORD,
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.autenticar'), [
            'email' => $user->email,
            'password' => self::NEW_PASSWORD,
        ])->assertRedirect(route('agendamento.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_remember_token_e_rotacionado(): void
    {
        $user = $this->user(['remember_token' => 'token-persistente-antigo']);
        $token = Password::createToken($user);

        $this->postReset($user, $token);

        $this->assertNotSame(
            'token-persistente-antigo',
            $user->fresh()->remember_token
        );
        $this->assertNotEmpty($user->fresh()->remember_token);
    }

    public function test_sessoes_anteriores_sao_removidas(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        DB::table('sessions')->insert([
            $this->sessionRow('sessao-anterior-1', $user),
            $this->sessionRow('sessao-anterior-2', $user),
        ]);

        $this->postReset($user, $token);

        $this->assertDatabaseMissing('sessions', [
            'user_id' => $user->id,
        ]);
    }

    public function test_usuario_permanece_desautenticado_apos_redefinicao(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->postReset($user, $token);

        $this->assertGuest();
    }

    public function test_sucesso_redireciona_para_login_com_mensagem_exata(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->postReset($user, $token)
            ->assertRedirect(route('login'))
            ->assertSessionHas(
                'success',
                'Senha redefinida com sucesso. Você já pode acessar a aplicação com sua nova senha.'
            );

        $this->get(route('login'))
            ->assertSee('Senha redefinida com sucesso. Você já pode acessar a aplicação com sua nova senha.');
    }

    public function test_rotas_de_recuperacao_possuem_guest_e_throttle_nos_posts(): void
    {
        foreach (['password.request', 'password.reset'] as $routeName) {
            $middleware = Route::getRoutes()
                ->getByName($routeName)
                ->gatherMiddleware();

            $this->assertContains('web', $middleware);
            $this->assertContains('guest', $middleware);
            $this->assertNotContains('login.throttle', $middleware);
        }

        foreach (['password.email', 'password.update'] as $routeName) {
            $middleware = Route::getRoutes()
                ->getByName($routeName)
                ->gatherMiddleware();

            $this->assertContains('web', $middleware);
            $this->assertContains('guest', $middleware);
            $this->assertContains('throttle:5,1', $middleware);
            $this->assertNotContains('login.throttle', $middleware);
        }
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create([
            'name' => 'Usuário de Teste',
            'email' => 'usuario'.uniqid().'@example.com',
            'password' => self::CURRENT_PASSWORD,
            'role' => 'lider',
            'is_active' => true,
            ...$attributes,
        ]);
    }

    private function postReset(
        User $user,
        string $token,
        string $password = self::NEW_PASSWORD,
        ?string $confirmation = null
    ): TestResponse {
        return $this->from($this->resetUrl($token, $user->email))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => $password,
                'password_confirmation' => $confirmation ?? $password,
            ]);
    }

    private function resetUrl(string $token, string $email): string
    {
        return route('password.reset', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    private function sessionRow(string $id, User $user): array
    {
        return [
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => Carbon::now()->timestamp,
        ];
    }
}
