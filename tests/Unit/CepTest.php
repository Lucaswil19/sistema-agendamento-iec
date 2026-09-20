<?php

namespace Tests\Unit;

use App\Models\Beneficiario;
use App\Support\Cep;
use PHPUnit\Framework\TestCase;

class CepTest extends TestCase
{
    public function test_normaliza_cep_com_e_sem_mascara(): void
    {
        $this->assertSame('88350000', Cep::normalize('88350-000'));
        $this->assertSame('88350000', Cep::normalize('88350000'));
    }

    public function test_normaliza_cep_com_espacos(): void
    {
        $this->assertSame('88350000', Cep::normalize(' 88350-000 '));
        $this->assertTrue(Cep::isValid(' 88350-000 '));
    }

    public function test_formata_cep_com_oito_digitos(): void
    {
        $this->assertSame('88350-000', Cep::format('88350000'));
    }

    public function test_valida_exatamente_oito_digitos(): void
    {
        $this->assertTrue(Cep::isValid('88350000'));
        $this->assertFalse(Cep::isValid('8835000'));
        $this->assertFalse(Cep::isValid('883500000'));
    }

    public function test_rejeita_caracteres_invalidos(): void
    {
        $this->assertSame('', Cep::normalize('88350A000'));
        $this->assertFalse(Cep::isValid('88350A000'));
    }

    public function test_rejeita_valor_vazio_ou_nulo(): void
    {
        $this->assertSame('', Cep::normalize(''));
        $this->assertSame('', Cep::normalize(null));
        $this->assertFalse(Cep::isValid(''));
        $this->assertFalse(Cep::isValid(null));
    }

    public function test_modelo_armazena_cep_canonico_expoe_mascara_e_aceita_nulo(): void
    {
        $beneficiario = new Beneficiario(['cep' => '88350-000']);

        $this->assertSame('88350000', $beneficiario->getAttributes()['cep']);
        $this->assertSame('88350-000', $beneficiario->cep);

        $beneficiario->cep = null;

        $this->assertNull($beneficiario->getAttributes()['cep']);
        $this->assertNull($beneficiario->cep);
    }
}
