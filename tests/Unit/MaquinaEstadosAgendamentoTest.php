<?php

namespace Tests\Unit;

use App\Models\Agendamento;
use App\Services\MaquinaEstadosAgendamento;
use DomainException;
use PHPUnit\Framework\TestCase;

class MaquinaEstadosAgendamentoTest extends TestCase
{
    public function test_matriz_de_transicoes_permite_apenas_os_fluxos_oficiais(): void
    {
        $maquina = new MaquinaEstadosAgendamento;
        $status = ['agendado', 'reagendado', 'completado', 'cancelado', 'perdido'];
        $permitidas = [
            'agendado' => ['reagendado', 'completado', 'cancelado', 'perdido'],
            'reagendado' => ['completado', 'cancelado', 'perdido'],
            'completado' => [],
            'cancelado' => ['reagendado'],
            'perdido' => ['reagendado'],
        ];

        foreach ($status as $origem) {
            foreach ($status as $destino) {
                $this->assertSame(
                    in_array($destino, $permitidas[$origem], true),
                    $maquina->podeTransicionar($origem, $destino),
                    "Resultado inesperado para {$origem} -> {$destino}."
                );
            }
        }
    }

    public function test_reabertura_limpa_motivo_e_incrementa_versao(): void
    {
        $agendamento = new Agendamento;
        $agendamento->forceFill([
            'status' => 'cancelado',
            'motivo_cancelamento' => 'Motivo anterior',
            'lock_version' => 4,
        ]);

        (new MaquinaEstadosAgendamento)->transicionar($agendamento, 'reagendado');

        $this->assertSame('reagendado', $agendamento->status);
        $this->assertNull($agendamento->motivo_cancelamento);
        $this->assertSame(5, $agendamento->lock_version);
    }

    public function test_status_completado_e_terminal(): void
    {
        $agendamento = new Agendamento;
        $agendamento->forceFill([
            'status' => 'completado',
            'lock_version' => 1,
        ]);

        $this->expectException(DomainException::class);

        (new MaquinaEstadosAgendamento)->transicionar($agendamento, 'reagendado');
    }
}
