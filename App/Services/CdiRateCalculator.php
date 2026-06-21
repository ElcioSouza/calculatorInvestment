<?php

namespace App\Services;

class CdiRateCalculator
{
    private const BUSINESS_DAYS_YEAR = 252;

    public function isAnnualRateValid(float $value): bool
    {
        return $value > 0.0 && $value < 100.0;
    }

    public function isDailyRateValid(float $value): bool
    {
        return $value > 0.0 && $value < 1.0;
    }

    public function annualizeDailyRate(float $dailyRate): float
    {
        return (pow(1.0 + $dailyRate / 100.0, self::BUSINESS_DAYS_YEAR) - 1.0) * 100.0;
    }
}