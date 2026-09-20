<?php

namespace App\Services;

use App\Exceptions\ViaCepException;
use App\Support\Cep;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

final class ViaCepService
{
    private const CACHE_TTL_DAYS = 7;

    /**
     * @return array{cep: string, logradouro: string, complemento: string, bairro: string, cidade: string, uf: string}
     */
    public function consultar(string $cep): array
    {
        $cepNormalizado = Cep::normalize($cep);

        if (! Cep::isValid($cepNormalizado)) {
            throw ViaCepException::invalidCep();
        }

        return Cache::remember(
            'viacep:'.$cepNormalizado,
            now()->addDays(self::CACHE_TTL_DAYS),
            fn (): array => $this->consultarServico($cepNormalizado)
        );
    }

    /**
     * @return array{cep: string, logradouro: string, complemento: string, bairro: string, cidade: string, uf: string}
     */
    private function consultarServico(string $cep): array
    {
        $baseUrl = rtrim(trim((string) config('services.viacep.url')), '/');

        if (! $this->isSecureUrl($baseUrl)) {
            throw ViaCepException::unavailable();
        }

        $url = $baseUrl.'/'.$cep.'/json/';

        try {
            $response = Http::acceptJson()
                ->connectTimeout(2)
                ->timeout(4)
                ->get($url);
        } catch (ConnectionException) {
            throw ViaCepException::unavailable();
        } catch (Throwable) {
            throw ViaCepException::unavailable();
        }

        if (! $response->successful()) {
            throw ViaCepException::unavailable();
        }

        try {
            $payload = $response->json();
        } catch (Throwable) {
            throw ViaCepException::invalidResponse();
        }

        if (! is_array($payload)) {
            throw ViaCepException::invalidResponse();
        }

        if (($payload['erro'] ?? false) === true || ($payload['erro'] ?? null) === 'true') {
            throw ViaCepException::notFound();
        }

        return $this->sanitizePayload($payload, $cep);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{cep: string, logradouro: string, complemento: string, bairro: string, cidade: string, uf: string}
     */
    private function sanitizePayload(array $payload, string $cep): array
    {
        foreach (['cep', 'logradouro', 'complemento', 'bairro', 'localidade', 'uf'] as $field) {
            if (! array_key_exists($field, $payload) || ! is_string($payload[$field])) {
                throw ViaCepException::invalidResponse();
            }
        }

        $cidade = trim($payload['localidade']);
        $uf = strtoupper(trim($payload['uf']));

        if (
            Cep::normalize($payload['cep']) !== $cep
            || $cidade === ''
            || preg_match('/^[A-Z]{2}$/', $uf) !== 1
        ) {
            throw ViaCepException::invalidResponse();
        }

        return [
            'cep' => Cep::format($cep),
            'logradouro' => trim($payload['logradouro']),
            'complemento' => trim($payload['complemento']),
            'bairro' => trim($payload['bairro']),
            'cidade' => $cidade,
            'uf' => $uf,
        ];
    }

    private function isSecureUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }
}
