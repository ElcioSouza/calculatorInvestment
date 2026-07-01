<?php
namespace App\Services;

use App\Contracts\CalculatesProfitInterface;
use App\Contracts\CalculatesTaxInterface;
use App\Contracts\FormatsAmountInterface;

class ProfitCalculationService extends ServiceBase implements CalculatesProfitInterface
{
    public function __construct(private CalculatesTaxInterface $taxService) {}

    public function calculateProfitBruto(string $initialCapital, string $amountBruto): string
    {
        return bcsub($amountBruto, $initialCapital, self::DEFAULT_SCALE);
    }

    public function calculateProfitLiquid(string $initialCapital, string $amountLiquid): string
    {
        return bcsub($amountLiquid, $initialCapital, self::DEFAULT_SCALE);
    }

    public function calculateDailyProfitLiquid(string $initialCapital, string $amountBruto, int $days, int $businessDays): string
    {
        $amountLiquid = $this->taxService->calculateIR($initialCapital, $amountBruto, $days);
        $profitLiquid = bcsub($amountLiquid, $initialCapital, self::DEFAULT_SCALE);

        return bcdiv($profitLiquid, (string) $businessDays, 4);
    }

    public function calculateDailyProfitLiquidIsento(string $initialCapital, string $amountBruto, int $businessDays): string
    {
        $profitBruto = bcsub($amountBruto, $initialCapital, self::DEFAULT_SCALE);

        return bcdiv($profitBruto, (string) $businessDays, 4);
    }

    public function calculateMonthlyProfitLiquid(string $initialCapital, string $amountBruto, int $days, int $businessDays, int $businessDaysInMonth): string
    {
        $amountLiquid = $this->taxService->calculateIR($initialCapital, $amountBruto, $days);
        $profitLiquid = bcsub($amountLiquid, $initialCapital, self::DEFAULT_SCALE);

        if ($businessDays <= 0) {
            return '0.00';
        }

        return number_format(((float) bcmul($profitLiquid, (string) $businessDaysInMonth, 6)) / $businessDays, 2, '.', '');
    }

    public function calculateMonthlyProfitLiquidIsento(string $initialCapital, string $amountBruto, int $businessDays, int $businessDaysInMonth): string
    {
        $profitBruto = bcsub($amountBruto, $initialCapital, self::DEFAULT_SCALE);

        if ($businessDays <= 0) {
            return '0.00';
        }

        return number_format(((float) bcmul($profitBruto, (string) $businessDaysInMonth, 6)) / $businessDays, 2, '.', '');
    }
}
