<?php
namespace App\Helpers;

use App\Contracts\CalculatesProfitInterface;
use App\Contracts\CalculatesRateInterface;
use App\Contracts\CountsBusinessDaysInterface;
use App\Contracts\FormatsAmountInterface;
use App\ValueObjects\InvestmentInput;
use DateTimeImmutable;

abstract class InvestmentCalculationHelper
{
    public function resolveDisplayPercentage(
        InvestmentInput $input,
        CalculatesRateInterface $rateService
    ): string {
        $rateType = strtolower(trim($input->rateType));

        if ($rateType === 'pre') {
            return bcdiv($input->preFixedAnnualRate, '1', 8);
        }

        $cdiCurrentRate = $input->cdiOver !== ''
            ? $input->cdiOver
            : $rateService->convertSelicMetaToOver($input->selicMeta, $input->selicIsOver);

        return $rateService->calculateByCDI($cdiCurrentRate, $input->cdiPercentage);
    }

    public function calculateGrossValues(
        InvestmentInput $input,
        string $dailyPercentage,
        DateTimeImmutable $redemptionDT,
        CountsBusinessDaysInterface $businessDayService,
        CalculatesRateInterface $rateService,
        CalculatesProfitInterface $profitService,
        FormatsAmountInterface $formatter
    ): array {
        $businessDaysForCalc = $businessDayService->countBusinessDays(
            $input->applicationDate,
            $redemptionDT->format('Y-m-d')
        );

        $amountBrutoRaw = $rateService->calculateAmountByBusinessDays(
            $input->initialCapital,
            $dailyPercentage,
            $businessDaysForCalc
        );

        $amountBruto    = $formatter->normalizeAmountRounded($amountBrutoRaw);
        $profitBrutoRaw = $profitService->calculateProfitBruto($input->initialCapital, $amountBrutoRaw);
        $profitBruto    = $formatter->normalizeAmountRounded($profitBrutoRaw);

        return [$amountBrutoRaw, $amountBruto, $profitBrutoRaw, $profitBruto];
    }
}
