<?php

namespace App\Support;

/**
 * Money is stored as integer minor units (paisa / cents) everywhere.
 */
final class Money
{
    public static function format(?int $minor, string $currency, bool $withCode = true): string
    {
        if ($minor === null) {
            return '—';
        }

        // Sign goes before the currency: "-Rs 2,400", not "Rs -2,400".
        $sign = $minor < 0 ? '-' : '';
        $major = abs($minor) / 100;
        $decimals = fmod($major, 1.0) === 0.0 ? 0 : 2;
        $number = number_format($major, $decimals);

        return $sign.match (strtoupper($currency)) {
            'USD' => '$'.$number,
            'PKR' => $withCode ? 'Rs '.$number : $number,
            default => $withCode ? strtoupper($currency).' '.$number : $number,
        };
    }

    public static function toMinor(float|int|string|null $major): ?int
    {
        if ($major === null || $major === '') {
            return null;
        }

        return (int) round(((float) $major) * 100);
    }

    /** Commission in minor units, rounded half-up. bps: 2000 = 20%. */
    public static function percentOf(int $minor, int $bps): int
    {
        return intdiv($minor * $bps + 5000, 10000);
    }
}
