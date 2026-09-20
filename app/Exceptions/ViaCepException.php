<?php

namespace App\Exceptions;

use RuntimeException;

final class ViaCepException extends RuntimeException
{
    public const NOT_FOUND = 'not_found';

    public const UNAVAILABLE = 'unavailable';

    public const INVALID_RESPONSE = 'invalid_response';

    public const INVALID_CEP = 'invalid_cep';

    private function __construct(
        private readonly string $reason,
        string $message,
        private readonly int $httpStatus
    ) {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self(
            self::NOT_FOUND,
            'CEP não encontrado. Confira o número informado ou preencha o endereço manualmente.',
            404
        );
    }

    public static function unavailable(): self
    {
        return new self(
            self::UNAVAILABLE,
            'Não foi possível consultar o CEP neste momento. Preencha o endereço manualmente.',
            503
        );
    }

    public static function invalidResponse(): self
    {
        return new self(
            self::INVALID_RESPONSE,
            'Não foi possível consultar o CEP neste momento. Preencha o endereço manualmente.',
            503
        );
    }

    public static function invalidCep(): self
    {
        return new self(
            self::INVALID_CEP,
            'Informe um CEP válido com oito dígitos.',
            422
        );
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
