<?php

namespace Tests\Feature;

use App\Models\User;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailTransportTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_aplica_limites_de_conexao_e_resposta_ao_enviar(): void
    {
        $history = [];
        $this->fakeResend([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"test-mail"}'),
        ], $history);

        Mail::raw('Mensagem de teste', fn ($message) => $message
            ->to('recipient@example.test')->subject('Teste'));

        $this->assertCount(1, $history);
        $this->assertSame(3.0, $history[0]['options']['connect_timeout']);
        $this->assertSame(8.0, $history[0]['options']['timeout']);
        $this->assertSame('https://api.resend.com/emails', (string) $history[0]['request']->getUri());
    }

    public function test_timeout_de_email_preserva_resposta_generica_da_recuperacao(): void
    {
        $history = [];
        $this->fakeResend([
            new ConnectException('Simulated timeout', new Request('POST', 'https://api.resend.com/emails')),
        ], $history);
        $user = User::factory()->create(['is_active' => true]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', 'Caso exista uma conta ativa associada ao e-mail informado, as instruções para redefinição da senha serão enviadas.');

        $this->assertCount(1, $history);
    }

    private function fakeResend(array $responses, array &$history): void
    {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($history));
        config([
            'mail.default' => 'resend',
            'mail.from.address' => 'sender@example.test',
            'mail.mailers.resend.key' => 'test-key',
            'mail.mailers.resend.base_uri' => 'api.resend.com',
            'mail.mailers.resend.timeout' => 8,
            'mail.mailers.resend.connect_timeout' => 3,
            'mail.mailers.resend.client' => ['handler' => $handler],
        ]);
    }
}
