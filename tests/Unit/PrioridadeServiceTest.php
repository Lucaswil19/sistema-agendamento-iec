<?php

namespace Tests\Unit;

use App\Models\Beneficiario;
use App\Models\Historico_familiar;
use App\Services\PrioridadeService;
use Carbon\Carbon;
use Tests\TestCase;

class PrioridadeServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_calcula_prioridade_urgente_quando_ha_varios_fatores_de_risco(): void
    {
        Carbon::setTestNow('2026-07-09');

        $beneficiario = new Beneficiario([
            'data_nascimento' => '1950-01-01',
            'possui_deficiencia' => true,
            'possui_problema_saude' => false,
        ]);

        $beneficiario->setRelation('historicoFamiliar', collect([
            new Historico_familiar([
                'data_nascimento' => '2020-01-01',
                'possui_deficiencia' => false,
                'possui_problema_saude' => false,
            ]),
            new Historico_familiar([
                'data_nascimento' => '1955-01-01',
                'possui_deficiencia' => false,
                'possui_problema_saude' => true,
            ]),
        ]));

        $resultado = (new PrioridadeService)->calcular($beneficiario);

        $this->assertSame('urgente', $resultado['nivel']);
        $this->assertSame(11, $resultado['pontos']);
        $this->assertStringContainsString('família com 3 membros', $resultado['justificativa']);
        $this->assertStringContainsString('1 criança(s)', $resultado['justificativa']);
        $this->assertStringContainsString('2 idoso(s)', $resultado['justificativa']);
    }

    public function test_calcula_prioridade_baixa_sem_fatores_agravantes(): void
    {
        Carbon::setTestNow('2026-07-09');

        $beneficiario = new Beneficiario([
            'data_nascimento' => '1990-01-01',
            'possui_deficiencia' => false,
            'possui_problema_saude' => false,
        ]);

        $beneficiario->setRelation('historicoFamiliar', collect());

        $resultado = (new PrioridadeService)->calcular($beneficiario);

        $this->assertSame('baixa', $resultado['nivel']);
        $this->assertSame(0, $resultado['pontos']);
        $this->assertSame(
            'Prioridade calculada automaticamente sem fatores agravantes identificados.',
            $resultado['justificativa']
        );
    }

    public function test_beneficiario_com_12_anos_conta_como_crianca(): void
    {
        Carbon::setTestNow('2026-07-13');

        $beneficiario = new Beneficiario([
            'data_nascimento' => '2014-07-13',
            'possui_deficiencia' => false,
            'possui_problema_saude' => false,
        ]);

        $beneficiario->setRelation('historicoFamiliar', collect());

        $resultado = (new PrioridadeService)->calcular($beneficiario);

        $this->assertSame(1, $resultado['pontos']);
        $this->assertStringContainsString('1 criança(s)', $resultado['justificativa']);
    }
}
