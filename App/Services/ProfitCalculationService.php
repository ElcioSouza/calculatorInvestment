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

    public function calculateMonthlyProfitLiquid(string $initialCapital, string $amountBruto, int $days, int $months): string
    {
        $amountLiquid = $this->taxService->calculateIR($initialCapital, $amountBruto, $days);
        $profitLiquid = bcsub($amountLiquid, $initialCapital, self::DEFAULT_SCALE);

        return number_format(((float) $profitLiquid) / $months, 2, '.', '');
    }

    public function calculateMonthlyProfitLiquidIsento(string $initialCapital, string $amountBruto, int $months): string
    {
        $profitBruto = bcsub($amountBruto, $initialCapital, self::DEFAULT_SCALE);

        return number_format(((float) $profitBruto) / $months, 2, '.', '');
    }
}
