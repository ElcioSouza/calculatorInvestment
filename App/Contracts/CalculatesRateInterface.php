<?php

namespace App\Contracts;

interface CalculatesRateInterface
{
    public function calculateByCDI(string $cdiCurrentRate, string $cdiPercentage): string;
    public function calculateRateByMonths(string $annualRate, int $months): string;
    public function calculateRateByMonthsCompound(string $annualRate, int $months): string;
    public function calculateDailyRateFromAnnual(string $annualRate): string;
    public function calculateAmountByBusinessDays(string $initialCapital, string $dailyRatePercent, int $businessDays): string;
    public function calculateAmountBruto(string $initialCapital, string $cdiCurrentRate): string;
    public function convertSelicMetaToOver(string $selicMeta, bool $isOver = false, string $spread = '0.19335938'): string;
}
