<?php

namespace Tests\Feature;

use App\Models\Beneficiario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ViaCepTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.viacep.url', 'https://viacep.test/ws');
        Cache::clear();
        Http::preventStrayRequests();
    }

    public function test_visitante_nao_acessa_consulta(): void
    {
        $this->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_voluntario_nao_acessa_consulta(): void
    {
        $this->actingAs($this->usuario('voluntario'))
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_secretaria_nao_acessa_consulta(): void
    {
        $this->actingAs($this->usuario('secretaria'))
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_lider_consulta_cep_com_mascara_e_recebe_somente_campos_necessarios(): void
    {
        Http::fake([
            'https://viacep.test/ws/*' => Http::response($this->respostaViaCep([
                'ibge' => '4202909',
                'ddd' => '47',
            ])),
        ]);

        $this->actingAs($this->usuario('lider'))
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350-000']))
            ->assertOk()
            ->assertExactJson([
                'cep' => '88350-000',
                'logradouro' => 'Rua Exemplo',
                'complemento' => '',
                'bairro' => 'Centro',
                'cidade' => 'Brusque',
                'uf' => 'SC',
            ]);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://viacep.test/ws/88350000/json/'
                && $request->data() === []
                && $request->hasHeader('Accept', 'application/json');
        });
    }

    public function test_cep_sem_mascara_e_aceito(): void
    {
        Http::fake([
            'https://viacep.test/ws/*' => Http::response($this->respostaViaCep()),
        ]);

        $this->actingAs($this->usuario('lider'))
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertOk()
            ->assertJsonPath('cep', '88350-000');
    }

    public function test_cep_invalido_retorna_422_sem_consulta_externa(): void
    {
        $this->actingAs($this->usuario('lider'))
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '8835A000']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cep')
            ->assertJsonPath('errors.cep.0', 'Informe um CEP válido com oito dígitos.');

        Http::assertNothingSent();
    }

    public function test_erro_true_e_tratado_como_cep_inexistente(): void
    {
        Http::fake([
            'https://viacep.test/ws/*' => Http::response(['erro' => true]),
        ]);

        $this->actingAs($this->usuario('lider'))
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '99999999']))
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'CEP não encontrado. Confira o número informado ou preencha o endereço manualmente.'
            );
    }

    public function test_falha_http_e_tratada_sem_expor_detalhes(): void
    {
        Http::fake([
            'https://viacep.test/ws/*' => Http::response(['detalhe' => 'interno'], 500),
        ]);

        $response = $this->actingAs($this->usuario('lider'))
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertServiceUnavailable()
            ->assertJsonPath(
                'message',
                'Não foi possível consultar o CEP neste momento. Preencha o endereço manualmente.'
            );

        $response->assertDontSee('detalhe');
        $response->assertDontSee('https://viacep.test');
    }

    public function test_timeout_e_tratado_como_indisponibilidade(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('cURL error 28: tempo limite excedido');
        });

        $this->actingAs($this->usuario('lider'))
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertServiceUnavailable();
    }

    public function test_falha_de_conexao_e_tratada_como_indisponibilidade(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('Falha de conexão com endereço técnico');
        });

        $response = $this->actingAs($this->usuario('lider'))
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertServiceUnavailable();

        $response->assertDontSee('Falha de conexão');
    }

    public function test_resposta_json_invalida_e_tratada(): void
    {
        Http::fake([
            'https://viacep.test/ws/*' => Http::response('{json inválido', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $this->actingAs($this->usuario('lider'))
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertServiceUnavailable();
    }

    public function test_resposta_incompleta_nao_quebra_a_aplicacao_nem_e_armazenada_em_cache(): void
    {
        $respostaIncompleta = $this->respostaViaCep();
        unset($respostaIncompleta['bairro']);

        Http::fake([
            'https://viacep.test/ws/*' => Http::response($respostaIncompleta),
        ]);
        $lider = $this->usuario('lider');

        $this->actingAs($lider)
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertServiceUnavailable();
        $this->actingAs($lider)
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertServiceUnavailable();

        Http::assertSentCount(2);
        $this->assertFalse(Cache::has('viacep:88350000'));
    }

    public function test_apenas_cep_normalizado_e_enviado_ao_servico(): void
    {
        Http::fake([
            'https://viacep.test/ws/*' => Http::response($this->respostaViaCep()),
        ]);

        $this->actingAs($this->usuario('lider'))
            ->getJson(route('beneficiarios.cep.consultar', [
                'cep' => '88350-000',
                'nome' => 'Pessoa não deve ser enviada',
                'cpf' => '111.444.777-35',
            ]))
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            $conteudo = $request->url().json_encode($request->data());

            return $request->url() === 'https://viacep.test/ws/88350000/json/'
                && ! str_contains($conteudo, 'Pessoa')
                && ! str_contains($conteudo, '11144477735');
        });
    }

    public function test_cache_evitar_consulta_externa_repetida(): void
    {
        Http::fake([
            'https://viacep.test/ws/*' => Http::response($this->respostaViaCep()),
        ]);
        $lider = $this->usuario('lider');

        $this->actingAs($lider)
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertOk();
        $this->actingAs($lider)
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350-000']))
            ->assertOk();

        Http::assertSentCount(1);
        $this->assertTrue(Cache::has('viacep:88350000'));
    }

    public function test_cep_inexistente_nao_e_armazenado_em_cache(): void
    {
        Http::fake([
            'https://viacep.test/ws/*' => Http::response(['erro' => true]),
        ]);
        $lider = $this->usuario('lider');

        $this->actingAs($lider)
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '99999999']))
            ->assertNotFound();
        $this->actingAs($lider)
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '99999999']))
            ->assertNotFound();

        Http::assertSentCount(2);
        $this->assertFalse(Cache::has('viacep:99999999'));
    }

    public function test_throttle_limita_consultas_excessivas(): void
    {
        Http::fake([
            'https://viacep.test/ws/*' => Http::response($this->respostaViaCep()),
        ]);
        $lider = $this->usuario('lider');
        $url = route('beneficiarios.cep.consultar', ['cep' => '88350000']);

        for ($tentativa = 1; $tentativa <= 30; $tentativa++) {
            $this->actingAs($lider)->getJson($url)->assertOk();
        }

        $this->actingAs($lider)->getJson($url)->assertTooManyRequests();
    }

    public function test_cadastro_armazena_cep_sem_mascara(): void
    {
        $dados = $this->dadosCadastro(['cep' => '88350-000']);

        $this->actingAs($this->usuario('lider'))
            ->post(route('beneficiarios.store'), $dados)
            ->assertRedirect();

        $this->assertDatabaseHas('beneficiarios', [
            'email' => $dados['email'],
            'cep' => '88350000',
        ]);
    }

    public function test_edicao_atualiza_o_cep(): void
    {
        $beneficiario = Beneficiario::forceCreate($this->dadosModelo());
        $dados = $this->dadosCadastro([
            'cpf' => $beneficiario->cpf,
            'email' => $beneficiario->email,
            'cep' => '01001-000',
        ]);

        $this->actingAs($this->usuario('lider'))
            ->put(route('beneficiarios.update', $beneficiario), $dados)
            ->assertRedirect(route('beneficiarios.show', $beneficiario));

        $this->assertSame('01001000', $beneficiario->fresh()->getRawOriginal('cep'));
    }

    public function test_dois_beneficiarios_podem_possuir_o_mesmo_cep(): void
    {
        $lider = $this->usuario('lider');

        $this->actingAs($lider)
            ->post(route('beneficiarios.store'), $this->dadosCadastro())
            ->assertRedirect();
        $this->actingAs($lider)
            ->post(route('beneficiarios.store'), $this->dadosCadastro([
                'nome_beneficiario' => 'Segundo Beneficiário',
                'cpf' => '111.444.777-35',
                'email' => 'segundo@example.com',
            ]))
            ->assertRedirect();

        $this->assertSame(2, Beneficiario::where('cep', '88350000')->count());
    }

    public function test_beneficiario_antigo_com_cep_nulo_pode_ser_visualizado(): void
    {
        $beneficiario = Beneficiario::forceCreate($this->dadosModelo(['cep' => null]));

        $this->actingAs($this->usuario('lider'))
            ->get(route('beneficiarios.show', $beneficiario))
            ->assertOk()
            ->assertSeeTextInOrder(['CEP', 'Não informado', 'Endereço', 'Rua Manual, 123']);
    }

    public function test_cadastro_manual_funciona_quando_viacep_falha(): void
    {
        Http::fake([
            'https://viacep.test/ws/*' => Http::response([], 503),
        ]);
        $lider = $this->usuario('lider');

        $this->actingAs($lider)
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '88350000']))
            ->assertServiceUnavailable();
        $this->actingAs($lider)
            ->post(route('beneficiarios.store'), $this->dadosCadastro([
                'endereco' => 'Endereço preenchido manualmente, 45',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('beneficiarios', [
            'endereco' => 'Endereço preenchido manualmente, 45',
            'cep' => '88350000',
        ]);
    }

    public function test_edicao_manual_funciona_quando_viacep_falha_e_preserva_endereco_ate_salvar(): void
    {
        Http::fake([
            'https://viacep.test/ws/*' => Http::response([], 503),
        ]);
        $beneficiario = Beneficiario::forceCreate($this->dadosModelo());
        $lider = $this->usuario('lider');

        $this->actingAs($lider)
            ->getJson(route('beneficiarios.cep.consultar', ['cep' => '01001000']))
            ->assertServiceUnavailable();

        $this->assertSame('Rua Manual, 123', $beneficiario->fresh()->endereco);

        $this->actingAs($lider)
            ->put(route('beneficiarios.update', $beneficiario), $this->dadosCadastro([
                'cpf' => $beneficiario->cpf,
                'email' => $beneficiario->email,
                'cep' => '01001000',
                'endereco' => 'Endereço corrigido manualmente, 500',
            ]))
            ->assertRedirect(route('beneficiarios.show', $beneficiario));

        $beneficiario->refresh();
        $this->assertSame('Endereço corrigido manualmente, 500', $beneficiario->endereco);
        $this->assertSame('01001000', $beneficiario->getRawOriginal('cep'));
    }

    /**
     * @param  array<string, mixed>  $sobrescrever
     * @return array<string, mixed>
     */
    private function respostaViaCep(array $sobrescrever = []): array
    {
        return array_merge([
            'cep' => '88350-000',
            'logradouro' => 'Rua Exemplo',
            'complemento' => '',
            'bairro' => 'Centro',
            'localidade' => 'Brusque',
            'uf' => 'SC',
        ], $sobrescrever);
    }

    private function usuario(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $sobrescrever
     * @return array<string, mixed>
     */
    private function dadosCadastro(array $sobrescrever = []): array
    {
        return array_merge([
            'nome_beneficiario' => 'Beneficiário ViaCEP',
            'cpf' => '123.456.789-09',
            'telefone' => '+55 (47) 99999-9999',
            'cep' => '88350000',
            'endereco' => 'Rua Manual, 123',
            'email' => 'viacep@example.com',
            'data_nascimento' => '10/05/1980',
            'possui_problema_saude' => '0',
            'possui_deficiencia' => '0',
        ], $sobrescrever);
    }

    /**
     * @param  array<string, mixed>  $sobrescrever
     * @return array<string, mixed>
     */
    private function dadosModelo(array $sobrescrever = []): array
    {
        return array_merge([
            'nome_beneficiario' => 'Beneficiário Antigo',
            'cpf' => '529.982.247-25',
            'telefone' => '+55 (47) 99999-9999',
            'cep' => '88350000',
            'endereco' => 'Rua Manual, 123',
            'email' => 'antigo@example.com',
            'data_nascimento' => '1990-01-01',
            'possui_problema_saude' => false,
            'possui_deficiencia' => false,
            'is_active' => true,
        ], $sobrescrever);
    }
}
