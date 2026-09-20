<?php

namespace Tests\Unit;

use App\Models\Agendamento;
use App\Models\Beneficiario;
use App\Models\Historico_acoes;
use App\Models\Historico_familiar;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class MassAssignmentTest extends TestCase
{
    public function test_campos_de_estado_e_auditoria_nao_sao_atribuiveis_em_massa(): void
    {
        $user = new User([
            'name' => 'Teste',
            'role' => 'lider',
            'is_active' => false,
        ]);
        $beneficiario = new Beneficiario([
            'nome_beneficiario' => 'Teste',
            'is_active' => false,
        ]);
        $agendamento = new Agendamento([
            'tipo_acao' => 'Visita',
            'user_id' => 99,
            'status' => 'completado',
            'lock_version' => 99,
            'prioridade' => 'urgente',
            'pontuacao_prioridade' => 99,
            'justificativa_prioridade' => 'Forjada',
            'motivo_cancelamento' => 'Forjado',
        ]);
        $familiar = new Historico_familiar([
            'nome' => 'Teste',
            'beneficiario_id' => 99,
        ]);
        $acao = new Historico_acoes([
            'descricao' => 'Teste',
            'beneficiario_id' => 99,
            'agendamento_id' => 99,
            'user_id' => 99,
        ]);

        $this->assertSame('Teste', $user->name);
        $this->assertArrayNotHasKey('role', $user->getAttributes());
        $this->assertArrayNotHasKey('is_active', $user->getAttributes());
        $this->assertArrayNotHasKey('is_active', $beneficiario->getAttributes());
        $this->assertArrayNotHasKey('user_id', $agendamento->getAttributes());
        $this->assertArrayNotHasKey('status', $agendamento->getAttributes());
        $this->assertArrayNotHasKey('lock_version', $agendamento->getAttributes());
        $this->assertArrayNotHasKey('prioridade', $agendamento->getAttributes());
        $this->assertArrayNotHasKey('pontuacao_prioridade', $agendamento->getAttributes());
        $this->assertArrayNotHasKey('justificativa_prioridade', $agendamento->getAttributes());
        $this->assertArrayNotHasKey('motivo_cancelamento', $agendamento->getAttributes());
        $this->assertArrayNotHasKey('beneficiario_id', $familiar->getAttributes());
        $this->assertArrayNotHasKey('beneficiario_id', $acao->getAttributes());
        $this->assertArrayNotHasKey('agendamento_id', $acao->getAttributes());
        $this->assertArrayNotHasKey('user_id', $acao->getAttributes());
    }
}
