<?php

namespace App\Support;

final class Cpf
{
    public static function normalize(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    public static function isValid(mixed $value): bool
    {
        $cpf = self::normalize($value);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;

            for ($index = 0; $index < $position; $index++) {
                $sum += ((int) $cpf[$index]) * (($position + 1) - $index);
            }

            $digit = ($sum * 10) % 11;
            $digit = $digit === 10 ? 0 : $digit;

            if ($digit !== (int) $cpf[$position]) {
                return false;
            }
        }

        return true;
    }

    public static function format(mixed $value): string
    {
        $cpf = self::normalize($value);

        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($cpf, 0, 3),
            substr($cpf, 3, 3),
            substr($cpf, 6, 3),
            substr($cpf, 9, 2)
        );
    }
}
