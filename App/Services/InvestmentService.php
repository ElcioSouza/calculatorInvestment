<?php
namespace App\Services;

use App\Contracts\CalculatesProfitInterface;
use App\Contracts\CalculatesRateInterface;
use App\Contracts\CalculatesTaxInterface;
use App\Contracts\CountsBusinessDaysInterface;
use App\Contracts\FormatsAmountInterface;
use App\Contracts\InvestmentRepositoryInterface;
use App\Helpers\InvestmentCalculationHelper as InvestmentCalculation;
use App\ValueObjects\Investment;
use App\ValueObjects\InvestmentInput;
use DateTimeImmutable;

class InvestmentService extends ServiceBase
{
    public function __construct(
        private CalculatesRateInterface $rateService,
        private CalculatesTaxInterface $taxService,
        private CalculatesProfitInterface $profitService,
        private CountsBusinessDaysInterface $businessDayService,
        private FormatsAmountInterface $formatter,
        private InvestmentCalculation $calculationInvestment,
        private InvestmentRepositoryInterface $repository,
    ) {}

    private ?int $lastSavedId = null;

    public function handle(InvestmentInput $input): Investment
    {
        $result = $this->process($input);

        $this->lastSavedId = $this->repository->save($input, $result);

        return $result;
    }

    public function getLastSavedId(): ?int
    {
        return $this->lastSavedId;
    }

    public function recalculateAndUpdate(int|string $id, InvestmentInput $input): Investment
    {
        $result = $this->recalculate($input);
        $this->repository->update($id, $input, $result);
        return $result;
    }

    public function recalculate(InvestmentInput $input): Investment
    {
        return $this->process($input);
    }

    private function process(InvestmentInput $input): Investment
    {
        $applicationDT = new DateTimeImmutable($input->applicationDate);
        $redemptionDT  = new DateTimeImmutable($input->redemptionDate);

        $days         = $this->resolveDays($applicationDT, $redemptionDT);
        $businessDays = $this->resolveBusinessDays($input);

        $displayPercentage = $this->calculationInvestment->resolveDisplayPercentage($input, $this->rateService);
        $dailyPercentage   = $this->rateService->calculateDailyRateFromAnnual($displayPercentage);
        [$amountBrutoRaw, $amountBruto, $profitBrutoRaw, $profitBruto] = $this->calculationInvestment->calculateGrossValues(
            $input,
            $dailyPercentage,
            $redemptionDT,
            $this->businessDayService,
            $this->rateService,
            $this->profitService,
            $this->formatter
        );
        $dailyProfitDisplay = $input->isIsento
            ? $this->profitService->calculateDailyProfitLiquidIsento(
                $input->initialCapital,
                $amountBrutoRaw,
                $businessDays
            )
            : $this->profitService->calculateDailyProfitLiquid(
                $input->initialCapital,
                $amountBrutoRaw,
                $days,
                $businessDays
            );
        [$iofValue, $amountLiquid, $iofRaw] = $this->calculateTaxValues(
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
            bcsub(bcsub($profitBrutoRaw, $iofRaw, 6), $profitLiquid, 6)
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
    private function calculateTaxValues(
        InvestmentInput $input,
        string $profitBrutoRaw,
        string $amountBrutoRaw,
        int $days,
        DateTimeImmutable $applicationDT,
        DateTimeImmutable $redemptionDT
    ): array {
        if ($input->isIsento) {
            return ['0.00', $amountBrutoRaw, '0.00'];
        }

        $iofLimitDT = $applicationDT->modify('+30 days');
        $iofEndDT   = $redemptionDT < $iofLimitDT ? $redemptionDT : $iofLimitDT;
        $daysForIOF = $applicationDT->diff($iofEndDT)->days;

        $iofRaw = $this->taxService->calculateIOFValue($profitBrutoRaw, $daysForIOF);
        $iofValue = $this->formatter->normalizeAmount($iofRaw);

        $amountLiquid = $this->taxService->calculateIR(
            $input->initialCapital,
            $amountBrutoRaw,
            $days,
            false,
            $iofRaw
        );

        return [$iofValue, $amountLiquid, $iofRaw];
    }
}
