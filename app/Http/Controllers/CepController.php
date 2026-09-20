<?php

namespace App\Http\Controllers;

use App\Exceptions\ViaCepException;
use App\Http\Requests\ConsultarCepRequest;
use App\Services\ViaCepService;
use Illuminate\Http\JsonResponse;
use Throwable;

final class CepController extends Controller
{
    public function consultar(
        ConsultarCepRequest $request,
        ViaCepService $viaCep
    ): JsonResponse {
        try {
            $endereco = $viaCep->consultar($request->validated()['cep']);
        } catch (ViaCepException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->httpStatus());
        } catch (Throwable) {
            return response()->json([
                'message' => 'Não foi possível consultar o CEP neste momento. Preencha o endereço manualmente.',
            ], 503);
        }

        return response()->json($endereco);
    }
}
