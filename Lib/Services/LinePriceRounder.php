<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

class LinePriceRounder
{
    public const MODE_NEAREST = 'nearest';
    public const MODE_NONE = 'none';
    public const MODE_PRECISION = 'precision';
    public const MODE_UP = 'up';

    public static function adjustNetPrice(
        float $netPrice,
        float $taxRate,
        string $mode,
        int $decimals
    ): float {
        if ($mode === self::MODE_NONE || $taxRate <= -100) {
            return $netPrice;
        }

        $grossPrice = $netPrice * (1 + $taxRate / 100);
        $roundedGross = self::roundGrossPrice($grossPrice, $mode, $decimals);
        return $roundedGross / (1 + $taxRate / 100);
    }

    public static function roundGrossPrice(float $price, string $mode, int $decimals): float
    {
        $decimals = max(0, min(6, $decimals));
        $factor = 10 ** $decimals;

        if ($mode === self::MODE_NEAREST) {
            return round($price, $decimals);
        }

        if ($mode === self::MODE_UP) {
            return ceil(($price * $factor) - 1e-9) / $factor;
        }

        if ($mode === self::MODE_PRECISION) {
            $candidate = round($price, $decimals);
            $tolerance = 10 ** (-$decimals - 3);
            return abs($price - $candidate) <= $tolerance ? $candidate : $price;
        }

        return $price;
    }
}
