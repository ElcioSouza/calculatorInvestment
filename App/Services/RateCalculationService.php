<?php

namespace App\Services;

use App\Contracts\CalculatesRateInterface;

class RateCalculationService extends ServiceBase implements CalculatesRateInterface
{
    public function __construct(
        private int $scale = 14
    ) {}

    public function calculateByCDI(string $cdiCurrentRate, string $cdiPercentage): string
    {
        $rateFactor = bcdiv($cdiCurrentRate, '100', $this->scale);
        $calculated = bcmul($rateFactor, $cdiPercentage, $this->scale);
        return bcdiv($calculated, '1', 8);
    }

    public function calculateRateByMonths(string $annualRate, int $months): string
    {
        $monthsRate = bcmul($annualRate, (string)$months, $this->scale);
        return bcdiv($monthsRate, '12', 2);
    }

    public function calculateRateByMonthsCompound(string $annualRate, int $months): string
    {
        $annualRateFloat = (float)$annualRate / 100.0;
        $periodRate      = pow(1.0 + $annualRateFloat, $months / 12.0) - 1.0;
        return sprintf('%.12F', $periodRate * 100.0);
    }

    public function calculateDailyRateFromAnnual(string $annualRate): string
    {
        $annualRateFloat = (float)$annualRate / 100.0;
        $dailyRate       = pow(1.0 + $annualRateFloat, 1.0 / 252.0) - 1.0;
        return sprintf('%.12F', $dailyRate * 100.0);
    }

    public function calculateAmountByBusinessDays(string $initialCapital, string $dailyRatePercent, int $businessDays): string
    {

        $dailyRate = (float)str_replace(',', '.', $dailyRatePercent) / 100.0;
        $saldo = round((float)$initialCapital, 2);
        for ($day = 0; $day < $businessDays; $day++) {
            $rendimento = round($saldo * $dailyRate, 2);
            $saldo = round($saldo + $rendimento, 2);
        }
        return sprintf('%.2F', $saldo);
    }

    public function calculateAmountBruto(string $initialCapital, string $cdiCurrentRate): string
    {
        $profit = bcdiv(bcmul($initialCapital, $cdiCurrentRate, $this->scale), '100', $this->scale);
        return bcadd($initialCapital, $profit, $this->scale);
    }
    public function convertSelicMetaToOver(string $selicMeta, bool $isOver = false, string $spread = '0.10'): string
    {
        if ($isOver) {
            return bcdiv($selicMeta, '1', 8);
        }

        return bcsub($selicMeta, $spread, $this->scale);
    }
}