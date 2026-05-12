<?php

namespace App\ValueObjects;

final class Investment
{
    public function __construct(
        public readonly string $amountBruto,
        public readonly string $amountLiquid,
        public readonly string $profitBruto,
        public readonly string $profitLiquid,
        public readonly string $iofValue,
        public readonly string $irTaxAmount,
        public readonly string $monthlyProfitLiquid,
        public readonly string $dailyProfitDisplay,
        public readonly bool   $isIsento,
        public readonly int    $days,
        public readonly int    $businessDays,
    ) {}
}
