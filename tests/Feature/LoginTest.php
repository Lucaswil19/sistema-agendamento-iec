<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LoginRateLimiter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_lider_ativo_consegue_autenticar(): void
    {
        $usuario = User::factory()->create([
            'password' => Hash::make('senha-segura'),
            'role' => 'lider',
            'is_active' => true,
        ]);

        $response = $this->post(route('login.autenticar'), [
            'email' => $usuario->email,
            'password' => 'senha-segura',
        ]);

        $response->assertRedirect(route('agendamento.index'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_usuario_inativo_nao_consegue_autenticar(): void
    {
        $usuario = User::factory()->create([
            'password' => Hash::make('senha-segura'),
            'role' => 'voluntario',
            'is_active' => false,
        ]);

        $response = $this->post(route('login.autenticar'), [
            'email' => $usuario->email,
            'password' => 'senha-segura',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_rejeita_senha_com_tipo_malformado(): void
    {
        $response = $this->post(route('login.autenticar'), [
            'email' => 'usuario@example.com',
            'password' => ['senha'],
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_login_bloqueia_forca_bruta_por_email_e_ip(): void
    {
        $email = 'limitador@example.com';
        $limitador = app(LoginRateLimiter::class);
        $chave = $limitador->attemptKey($this->loginRequest($email));
        RateLimiter::clear($chave);

        for ($tentativa = 1; $tentativa <= 5; $tentativa++) {
            $response = $this->post(route('login.autenticar'), [
                'email' => $email,
                'password' => 'SenhaIncorreta!123',
            ]);

            $response->assertSessionHasErrors('email');
        }

        $response = $this->post(route('login.autenticar'), [
            'email' => $email,
            'password' => 'SenhaIncorreta!123',
        ]);

        $response->assertStatus(429);
        $response->assertHeader('X-RateLimit-Limit', '5');
        $this->assertGreaterThan(0, (int) $response->headers->get('Retry-After'));
        $this->assertLessThanOrEqual(60, (int) $response->headers->get('Retry-After'));
        RateLimiter::clear($chave);
    }

    public function test_login_aumenta_progressivamente_o_tempo_de_bloqueio(): void
    {
        Carbon::setTestNow('2026-07-16 12:00:00');
        config()->set('auth.login_throttle.lockout_seconds', [60, 300, 900, 3600]);
        $credenciais = [
            'email' => 'reincidente@example.com',
            'password' => 'SenhaIncorreta!123',
        ];

        foreach ([60, 300, 900, 3600] as $segundosEsperados) {
            for ($tentativa = 1; $tentativa <= 5; $tentativa++) {
                $this->post(route('login.autenticar'), $credenciais)
                    ->assertSessionHasErrors('email');

                if ($segundosEsperados === 60 && $tentativa < 5) {
                    $this->travel(10)->seconds();
                }
            }

            $bloqueio = $this->post(route('login.autenticar'), $credenciais);

            $bloqueio->assertStatus(429);
            $bloqueio->assertHeader('Retry-After', (string) $segundosEsperados);

            $this->travel($segundosEsperados + 1)->seconds();
        }
    }

    public function test_limitador_isola_emails_e_ips_diferentes(): void
    {
        $credenciais = [
            'email' => 'isolado@example.com',
            'password' => 'SenhaIncorreta!123',
        ];

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1']);

        for ($tentativa = 1; $tentativa <= 5; $tentativa++) {
            $this->post(route('login.autenticar'), $credenciais)
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login.autenticar'), $credenciais)->assertStatus(429);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->post(route('login.autenticar'), $credenciais)
            ->assertSessionHasErrors('email');

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->post(route('login.autenticar'), [
                'email' => 'outro@example.com',
                'password' => 'SenhaIncorreta!123',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_login_valido_normaliza_email_e_limpa_tentativas_anteriores(): void
    {
        $usuario = User::factory()->create([
            'email' => 'usuario@example.com',
            'password' => Hash::make('SenhaCorreta!123'),
            'role' => 'lider',
            'is_active' => true,
        ]);
        $limitador = app(LoginRateLimiter::class);
        $chave = $limitador->attemptKey($this->loginRequest('usuario@example.com'));
        RateLimiter::clear($chave);

        Carbon::setTestNow('2026-07-16 12:00:00');

        for ($tentativa = 1; $tentativa <= 5; $tentativa++) {
            $this->post(route('login.autenticar'), [
                'email' => '  USUARIO@EXAMPLE.COM ',
                'password' => 'SenhaIncorreta!123',
            ])->assertSessionHasErrors('email');
        }

        $this->travel(61)->seconds();

        $this->post(route('login.autenticar'), [
            'email' => '  USUARIO@EXAMPLE.COM ',
            'password' => 'SenhaCorreta!123',
        ])->assertRedirect(route('agendamento.index'));

        $this->assertAuthenticatedAs($usuario);
        $this->assertSame(0, RateLimiter::attempts($chave));

        $this->post(route('logout'))->assertRedirect(route('login'));

        for ($tentativa = 1; $tentativa <= 5; $tentativa++) {
            $this->post(route('login.autenticar'), [
                'email' => 'usuario@example.com',
                'password' => 'SenhaIncorreta!123',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.autenticar'), [
            'email' => 'usuario@example.com',
            'password' => 'SenhaIncorreta!123',
        ])->assertHeader('Retry-After', '60');
    }

    private function loginRequest(string $email, string $ip = '127.0.0.1'): Request
    {
        return Request::create(
            uri: '/login',
            method: 'POST',
            parameters: ['email' => $email],
            server: ['REMOTE_ADDR' => $ip]
        );
    }
}
