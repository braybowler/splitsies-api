<?php

namespace App\Enums;

/**
 * Supported trip currencies (curated launch set; expand as needed).
 *
 * Backing value is the ISO 4217 code. Each case knows its minor-unit exponent
 * — the power of ten between the currency's major unit and the integer minor
 * units money is stored in (USD 1.00 -> 100, JPY 1 -> 1, KWD 1.000 -> 1000).
 */
enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case CAD = 'CAD';
    case AUD = 'AUD';
    case NZD = 'NZD';
    case CHF = 'CHF';
    case JPY = 'JPY';
    case CNY = 'CNY';
    case INR = 'INR';
    case MXN = 'MXN';
    case BRL = 'BRL';
    case SEK = 'SEK';
    case NOK = 'NOK';
    case DKK = 'DKK';
    case KWD = 'KWD';

    /**
     * Number of decimal places between the major unit and stored minor units.
     */
    public function minorUnitExponent(): int
    {
        return match ($this) {
            self::JPY => 0,
            self::KWD => 3,
            default => 2,
        };
    }
}
