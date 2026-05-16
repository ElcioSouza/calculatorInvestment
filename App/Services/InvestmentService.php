<?php
namespace App\Services;

use DateTimeImmutable;
use App\Contracts\CalculatesProfitInterface;
use App\Contracts\CalculatesRateInterface;
use App\Contracts\CalculatesTaxInterface;
use App\Contracts\CountsBusinessDaysInterface;
use App\Contracts\FormatsAmountInterface;
use App\Contracts\InvestmentRepositoryInterface;
use App\ValueObjects\Investment;
use App\ValueObjects\InvestmentInput;

class InvestmentService
{
    public function __construct(
        private CalculatesRateInterface $rateService,
        private CalculatesTaxInterface $taxService,
        private CalculatesProfitInterface $profitService,
        private CountsBusinessDaysInterface $businessDayService,
        private FormatsAmountInterface $formatter,
        private InvestmentRepositoryInterface $repository,
    ) {}

    public function calculate(InvestmentInput $input): Investment
    {
        return $this->handle($input);
    }

    public function handle(InvestmentInput $input): Investment
    {
    
        $result = $this->process($input);

        return $this->repository->save($input, $result);
    }

    private function process(InvestmentInput $input): Investment
    {
        $applicationDT = new DateTimeImmutable($input->applicationDate);
        $redemptionDT  = new DateTimeImmutable($input->redemptionDate);

        $days         = $this->resolveDays($applicationDT, $redemptionDT);
        $businessDays = $this->resolveBusinessDays($input);

        $displayPercentage = $this->resolveDisplayPercentage($input);
        $dailyPercentage   = $this->rateService->calculateDailyRateFromAnnual($displayPercentage);

        $dailyProfitDisplay                                            = $this->calculateDailyProfitDisplay($input, $dailyPercentage);
        [$amountBrutoRaw, $amountBruto, $profitBrutoRaw, $profitBruto] = $this->calculateGrossValues(
            $input,
            $dailyPercentage,
            $redemptionDT
        );
        [$iofValue, $amountLiquid] = $this->calculateTaxValues(
            $input,
            $profitBrutoRaw,
            $amountBrutoRaw,
            $days,
            $applicationDT,
            $redemptionDT
        );

        $amountLiquidNorm = $this->formatter->normalizeAmountRounded($amountLiquid);
        $profitLiquid     = $this->profitService->calculateProfitLiquid($input->initialCapital, $amountLiquid);
        $profitLiquidFmt  = $this->formatter->normalizeAmountRounded($profitLiquid);

        $monthlyProfitLiquid = $input->isIsento
            ? bcdiv($profitBrutoRaw, (string) $input->months, 2)
            : bcdiv($profitLiquid, (string) $input->months, 2);

        $irTaxAmount = $input->isIsento
            ? '0.00'
            : $this->formatter->normalizeAmount(
            bcsub(bcsub($profitBrutoRaw, $iofValue, 6), $profitLiquid, 6)
        );

        return new Investment(
            amountBruto: $amountBruto,
            amountLiquid: $amountLiquidNorm,
            profitBruto: $profitBruto,
            profitLiquid: $profitLiquidFmt,
            iofValue: $iofValue,
            irTaxAmount: $irTaxAmount,
            monthlyProfitLiquid: $monthlyProfitLiquid,
            dailyProfitDisplay: $dailyProfitDisplay,
            isIsento: $input->isIsento,
            days: $days,
            businessDays: $businessDays,
        );
    }
    private function resolveDays(DateTimeImmutable $applicationDT, DateTimeImmutable $redemptionDT): int
    {
        return $applicationDT->diff($redemptionDT)->days;
    }
    private function resolveBusinessDays(InvestmentInput $input): int
    {
        return $this->businessDayService->countBusinessDays(
            $input->applicationDate,
            $input->redemptionDate
        );
    }
    private function resolveDisplayPercentage(InvestmentInput $input): string
    {
        $rateType = strtolower(trim($input->rateType));

        if ($rateType === 'pre') {
            return bcdiv($input->preFixedAnnualRate, '1', 8);
        }

        $cdiCurrentRate = $input->cdiOver !== ''
            ? $input->cdiOver
            : $this->rateService->convertSelicMetaToOver($input->selicMeta, $input->selicIsOver);

        return $this->rateService->calculateByCDI($cdiCurrentRate, $input->cdiPercentage);
    }

    private function calculateGrossValues(
        InvestmentInput $input,
        string $dailyPercentage,
        DateTimeImmutable $redemptionDT
    ): array {
        $businessDaysForCalc = $this->businessDayService->countBusinessDays(
            $input->applicationDate,
            $redemptionDT->format('Y-m-d')
        );

        $amountBrutoRaw = $this->rateService->calculateAmountByBusinessDays(
            $input->initialCapital,
            $dailyPercentage,
            $businessDaysForCalc
        );

        $amountBruto    = $this->formatter->normalizeAmountRounded($amountBrutoRaw);
        $profitBrutoRaw = $this->profitService->calculateProfitBruto($input->initialCapital, $amountBrutoRaw);
        $profitBruto    = $this->formatter->normalizeAmountRounded($profitBrutoRaw);

        return [$amountBrutoRaw, $amountBruto, $profitBrutoRaw, $profitBruto];
    }
    private function calculateTaxValues(
        InvestmentInput $input,
        string $profitBrutoRaw,
        string $amountBrutoRaw,
        int $days,
        DateTimeImmutable $applicationDT,
        DateTimeImmutable $redemptionDT
    ): array {
        if ($input->isIsento) {
            return ['0.00', $amountBrutoRaw];
        }

        $iofLimitDT = $applicationDT->modify('+30 days');
        $iofEndDT   = $redemptionDT < $iofLimitDT ? $redemptionDT : $iofLimitDT;
        $daysForIOF = $applicationDT->diff($iofEndDT)->days;

        $iofValue = $this->formatter->normalizeAmount(
            $this->taxService->calculateIOFValue($profitBrutoRaw, $daysForIOF)
        );

        $amountLiquid = $this->taxService->calculateIR(
            $input->initialCapital,
            $amountBrutoRaw,
            $days,
            false
        );

        return [$iofValue, $amountLiquid];
    }
}
