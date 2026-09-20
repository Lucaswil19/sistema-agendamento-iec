<?php

namespace Tests\Feature;

use App\Models\Beneficiario;
use Carbon\Carbon;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_aplicacao_utiliza_timezone_configurado_para_sao_paulo(): void
    {
        $this->assertSame('America/Sao_Paulo', config('app.timezone'));
        $this->assertSame('America/Sao_Paulo', date_default_timezone_get());
    }

    public function test_regras_de_data_respeitam_o_dia_brasileiro_antes_da_meia_noite(): void
    {
        // 02:30 UTC do dia 17 ainda corresponde a 23:30 do dia 16 em Sao Paulo.
        Carbon::setTestNow(Carbon::create(2026, 7, 17, 2, 30, 0, 'UTC'));

        $beneficiario = new Beneficiario(['data_nascimento' => '2000-07-17']);
        $agendamentoAposMeiaNoite = Carbon::create(
            2026,
            7,
            17,
            0,
            30,
            0,
            config('app.timezone')
        );

        $this->assertSame('2026-07-16', today()->toDateString());
        $this->assertSame(25, $beneficiario->idade);
        $this->assertTrue($agendamentoAposMeiaNoite->isFuture());
    }

    public function test_regras_de_data_avancam_somente_apos_a_meia_noite_brasileira(): void
    {
        // 03:30 UTC do dia 17 corresponde a 00:30 do dia 17 em Sao Paulo.
        Carbon::setTestNow(Carbon::create(2026, 7, 17, 3, 30, 0, 'UTC'));

        $beneficiario = new Beneficiario(['data_nascimento' => '2000-07-17']);
        $instanteAnterior = Carbon::create(
            2026,
            7,
            17,
            0,
            15,
            0,
            config('app.timezone')
        );

        $this->assertSame('2026-07-17', today()->toDateString());
        $this->assertSame(26, $beneficiario->idade);
        $this->assertFalse($instanteAnterior->isFuture());
    }
}
