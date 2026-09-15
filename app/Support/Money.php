<?php

namespace App\Support;

class Money
{
    /**
     * Convert a validated amount like "25", "25.5" or "25.50" into cents.
     */
    public static function toCents(string|int $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    /**
     * Convert cents into the plain "25.00" format used by form inputs.
     */
    public static function fromCents(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /**
     * Format cents for display, e.g. 2500 becomes "$25.00".
     */
    public static function format(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }
}
