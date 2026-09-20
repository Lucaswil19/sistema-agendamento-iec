<?php

namespace Tests\Unit;

use App\Http\Controllers\BeneficiariosController;
use App\Models\Beneficiario;
use Carbon\Carbon;
use ReflectionClass;
use Tests\TestCase;

class AutoloadCaseSensitivityTest extends TestCase
{
    public function test_nome_do_arquivo_do_controller_respeita_a_classe_em_ambiente_case_sensitive(): void
    {
        $diretorio = app_path('Http/Controllers');
        $arquivos = scandir($diretorio);

        $this->assertIsArray($arquivos);
        $this->assertContains('BeneficiariosController.php', $arquivos);
        $this->assertNotContains('beneficiariosController.php', $arquivos);
        $this->assertTrue(class_exists(BeneficiariosController::class));

        $arquivoCarregado = (new ReflectionClass(BeneficiariosController::class))->getFileName();

        $this->assertNotFalse($arquivoCarregado);
        $this->assertSame('BeneficiariosController.php', basename($arquivoCarregado));
    }

    public function test_modelo_beneficiario_importa_e_carrega_carbon_com_namespace_canonico(): void
    {
        $conteudo = file_get_contents(app_path('Models/Beneficiario.php'));

        $this->assertNotFalse($conteudo);
        $this->assertStringContainsString('use Carbon\\Carbon;', $conteudo);
        $this->assertStringNotContainsString('use carbon\\Carbon;', $conteudo);

        Carbon::setTestNow(Carbon::create(2026, 7, 15));

        try {
            $beneficiario = new Beneficiario([
                'data_nascimento' => '2000-07-15',
            ]);

            $this->assertSame(26, $beneficiario->idade);
        } finally {
            Carbon::setTestNow();
        }
    }
}
