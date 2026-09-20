<?php

namespace App\Support;

final class Cep
{
    public static function normalize(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        if (is_string($value) && preg_match('/[^\d\s-]/u', $value) === 1) {
            return '';
        }

        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    public static function isValid(mixed $value): bool
    {
        return preg_match('/^\d{8}$/', self::normalize($value)) === 1;
    }

    public static function format(mixed $value): string
    {
        $cep = self::normalize($value);

        if (! self::isValid($cep)) {
            return $cep;
        }

        return substr($cep, 0, 5).'-'.substr($cep, 5, 3);
    }
}
