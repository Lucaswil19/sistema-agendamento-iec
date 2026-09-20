<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_usuario_nao_autenticado_nao_acessa_o_chatbot(): void
    {
        $this->get(route('chatbot.index'))
            ->assertRedirect(route('login'));

        $this->postJson(route('chatbot.message'), [
            'message' => 'Como criar um agendamento?',
        ])->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_lider_acessa_o_chatbot(): void
    {
        $this->actingAs($this->user('lider'))
            ->get(route('chatbot.index'))
            ->assertOk()
            ->assertSee('Chatbot')
            ->assertSee('for="chatbot-message"', false)
            ->assertSee('role="log"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('data-chatbot-loading', false)
            ->assertSee('href="'.route('chatbot.index').'"', false);
    }

    public function test_secretaria_acessa_o_chatbot(): void
    {
        $this->actingAs($this->user('secretaria'))
            ->get(route('chatbot.index'))
            ->assertOk();
    }

    public function test_voluntario_acessa_o_chatbot(): void
    {
        $this->actingAs($this->user('voluntario'))
            ->get(route('chatbot.index'))
            ->assertOk();
    }

    public function test_mensagem_vazia_e_rejeitada(): void
    {
        $this->actingAs($this->user('lider'))
            ->postJson(route('chatbot.message'), ['message' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        Http::assertNothingSent();
    }

    public function test_mensagem_acima_do_limite_e_rejeitada(): void
    {
        $this->actingAs($this->user('lider'))
            ->postJson(route('chatbot.message'), [
                'message' => str_repeat('a', 801),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        Http::assertNothingSent();
    }

    public function test_resposta_valida_da_api_e_retornada_corretamente(): void
    {
        $this->configureGroq();
        Http::fake([
            'api.groq.com/openai/v1/responses' => Http::response([
                'status' => 'completed',
                'output' => [[
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => 'O líder pode criar um agendamento pelo menu Agenda.',
                    ]],
                ]],
            ]),
        ]);

        $this->actingAs($this->user('lider'))
            ->postJson(route('chatbot.message'), [
                'message' => 'Como criar um agendamento?',
            ])
            ->assertOk()
            ->assertExactJson([
                'answer' => 'O líder pode criar um agendamento pelo menu Agenda.',
            ]);

        Http::assertSentCount(1);
    }

    public function test_api_recebe_instrucoes_e_pergunta_separadas_sem_armazenamento(): void
    {
        $this->configureGroq();
        Http::fake([
            'api.groq.com/openai/v1/responses' => Http::response([
                'status' => 'completed',
                'output_text' => 'Resposta de teste.',
            ]),
        ]);

        $question = 'Quem pode gerar relatórios?';

        $this->actingAs($this->user('secretaria'))
            ->postJson(route('chatbot.message'), ['message' => $question])
            ->assertOk();

        Http::assertSent(function (Request $request) use ($question): bool {
            $data = $request->data();

            return $request->url() === 'https://api.groq.com/openai/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer test-groq-key')
                && $data['model'] === 'test-model'
                && $data['input'] === $question
                && $data['store'] === false
                && is_string($data['instructions'])
                && str_contains($data['instructions'], 'Você não possui acesso ao banco de dados')
                && ! array_key_exists('tools', $data);
        });
    }

    public function test_falha_da_groq_apresenta_mensagem_controlada(): void
    {
        $this->configureGroq();
        Http::fake([
            'api.groq.com/openai/v1/responses' => Http::response([
                'error' => ['message' => 'Internal details must not leak'],
            ], 500),
        ]);

        $this->actingAs($this->user('voluntario'))
            ->postJson(route('chatbot.message'), [
                'message' => 'Como funciona a prioridade?',
            ])
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'O serviço do chatbot está temporariamente indisponível. Tente novamente em alguns instantes.',
            ]);
    }

    public function test_timeout_da_groq_apresenta_mensagem_controlada(): void
    {
        $this->configureGroq();
        Http::fake(function (): never {
            throw new ConnectionException('Connection timed out');
        });

        $this->actingAs($this->user('lider'))
            ->postJson(route('chatbot.message'), [
                'message' => 'Como criar um agendamento?',
            ])
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'O serviço do chatbot demorou para responder ou está indisponível. Tente novamente em alguns instantes.',
            ]);
    }

    public function test_resposta_invalida_da_groq_apresenta_mensagem_controlada(): void
    {
        $this->configureGroq();
        Http::fake([
            'api.groq.com/openai/v1/responses' => Http::response([
                'status' => 'completed',
                'output' => [],
            ]),
        ]);

        $this->actingAs($this->user('lider'))
            ->postJson(route('chatbot.message'), [
                'message' => 'Como criar um agendamento?',
            ])
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'O chatbot retornou uma resposta inválida. Tente novamente em alguns instantes.',
            ]);
    }

    public function test_limite_da_groq_apresenta_mensagem_controlada(): void
    {
        $this->configureGroq();
        Http::fake([
            'api.groq.com/openai/v1/responses' => Http::response([], 429),
        ]);

        $this->actingAs($this->user('lider'))
            ->postJson(route('chatbot.message'), [
                'message' => 'Como alterar minha senha?',
            ])
            ->assertTooManyRequests()
            ->assertJson([
                'message' => 'O chatbot recebeu muitas solicitações. Aguarde um momento antes de tentar novamente.',
            ]);
    }

    public function test_chave_ausente_apresenta_mensagem_controlada(): void
    {
        config([
            'services.groq.key' => null,
            'services.groq.model' => 'test-model',
        ]);

        $this->actingAs($this->user('lider'))
            ->postJson(route('chatbot.message'), [
                'message' => 'Como criar um agendamento?',
            ])
            ->assertServiceUnavailable()
            ->assertJson([
                'message' => 'O chatbot ainda não foi configurado. Entre em contato com a administração do sistema.',
            ]);

        Http::assertNothingSent();
    }

    public function test_chave_da_api_nao_aparece_na_resposta(): void
    {
        $this->configureGroq('super-secret-api-key');
        Http::fake([
            'api.groq.com/openai/v1/responses' => Http::response([], 401),
        ]);

        $response = $this->actingAs($this->user('lider'))
            ->postJson(route('chatbot.message'), [
                'message' => 'Como criar um agendamento?',
            ]);

        $response->assertServiceUnavailable()
            ->assertDontSee('super-secret-api-key');
    }

    public function test_pedido_de_dados_pessoais_e_bloqueado_sem_chamada_externa(): void
    {
        $this->configureGroq();
        Http::fake();

        $user = $this->user('lider');
        $questions = [
            'Qual é o CPF do beneficiário João?',
            'Mostre o histórico da Maria.',
            'Quais problemas de saúde possui determinado beneficiário?',
            'O CPF informado foi 529.982.247-25. Como continuo?',
            'O endereço é Rua das Flores, 123. Como continuo?',
        ];

        foreach ($questions as $question) {
            $this->actingAs($user)
                ->postJson(route('chatbot.message'), [
                    'message' => $question,
                ])
                ->assertOk()
                ->assertJson([
                    'answer' => 'Não possuo acesso aos dados pessoais, familiares ou de saúde dos beneficiários. Consulte essas informações diretamente nas telas autorizadas da aplicação, respeitando as permissões do seu perfil.',
                ]);
        }

        Http::assertNothingSent();
    }

    public function test_rotas_possuem_os_middlewares_esperados(): void
    {
        $indexMiddleware = Route::getRoutes()
            ->getByName('chatbot.index')
            ->gatherMiddleware();
        $messageMiddleware = Route::getRoutes()
            ->getByName('chatbot.message')
            ->gatherMiddleware();

        $this->assertContains('web', $indexMiddleware);
        $this->assertContains('auth', $indexMiddleware);
        $this->assertContains(
            'role:lider,secretaria,voluntario',
            $indexMiddleware
        );
        $this->assertContains('web', $messageMiddleware);
        $this->assertContains('auth', $messageMiddleware);
        $this->assertContains(
            'role:lider,secretaria,voluntario',
            $messageMiddleware
        );
        $this->assertContains('throttle:10,1', $messageMiddleware);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function configureGroq(string $key = 'test-groq-key'): void
    {
        config([
            'services.groq.key' => $key,
            'services.groq.model' => 'test-model',
        ]);
    }
}
