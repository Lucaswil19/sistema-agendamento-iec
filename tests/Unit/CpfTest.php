<?php

namespace Tests\Unit;

use App\Models\Beneficiario;
use App\Support\Cpf;
use PHPUnit\Framework\TestCase;

class CpfTest extends TestCase
{
    public function test_normaliza_valida_e_formata_cpf(): void
    {
        $this->assertSame('11144477735', Cpf::normalize('111.444.777-35'));
        $this->assertTrue(Cpf::isValid('11144477735'));
        $this->assertSame('111.444.777-35', Cpf::format('11144477735'));
        $this->assertFalse(Cpf::isValid('000.000.000-00'));
        $this->assertFalse(Cpf::isValid('123'));
    }

    public function test_modelo_armazena_cpf_canonico_e_expoe_mascara(): void
    {
        $beneficiario = new Beneficiario(['cpf' => '111.444.777-35']);

        $this->assertSame('11144477735', $beneficiario->getAttributes()['cpf']);
        $this->assertSame('111.444.777-35', $beneficiario->cpf);
    }
}
