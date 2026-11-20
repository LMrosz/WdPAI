<?php

declare(strict_types=1);

namespace App\Service;

/** Zamienia kwotę z bazy (NUMERIC) na napis do wyświetlenia; jedno miejsce dla wszystkich repozytoriów. */
final class PriceFormatter
{
    /** "165.00" na "165 zł", "72.50" na "72,50 zł". */
    public static function format(string $amount): string
    {
        $value = (float) $amount;
        $decimals = floor($value) === $value ? 0 : 2;

        return number_format($value, $decimals, ',', ' ') . ' zł';
    }
}
