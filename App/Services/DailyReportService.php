<?php
namespace App\Services;

use App\Services\AmountFormatterService;
use App\Services\BusinessDayService;
use App\Helpers\InvestmentCalculationHelper as InvestmentCalculation;
use App\Services\ProfitCalculationService;
use App\Services\RateCalculationService;
use App\Services\TaxCalculationService;
use App\ValueObjects\Investment;
use App\ValueObjects\InvestmentInput;
use DateTimeImmutable;

class DailyReportService
{
    private int $tableWidth = 104;

    public function __construct(
        private RateCalculationService $rateService,
        private BusinessDayService $businessDayService,
        private TaxCalculationService $taxService,
        private ProfitCalculationService $profitService,
        private InvestmentCalculation $calculationInvestment,
        private AmountFormatterService $formatterService
    ) {}
    public function generate(InvestmentInput $input, Investment $result): void
    {
        $applicationDate = new DateTimeImmutable($input->applicationDate);
        $redemptionDate  = new DateTimeImmutable($input->redemptionDate);

        $iofPeriodEnd = $applicationDate->modify('+30 days');
        $periodEndDate = $this->getLoopEndDate($redemptionDate, $iofPeriodEnd);
        $businessDaysInPeriod = $this->countBusinessDaysUntil(
            $input->applicationDate,
            $iofPeriodEnd->modify('+1 day')->format('Y-m-d')
        );

        $dailyPercentage = $this->getDailyPercentage($input);
        [$amountBrutoRaw, $amountBruto, $profitBrutoRaw, $profitBruto] = $this->calculationInvestment->calculateGrossValues(
            $input,
            $dailyPercentage,
            $redemptionDate,
            $this->businessDayService,
            $this->rateService,
            $this->profitService,
            $this->formatterService
        );
        $amountDays = $applicationDate->diff($redemptionDate)->days;
        $currentBusinessDays = $this->resolveBusinessDays($input);

        $this->printHeader();

        for ($day = $applicationDate, $displayDay = 0; $day <= $periodEndDate; $day = $day->modify('+1 day'), $displayDay++) {
            $this->printDayRow(
                $input,
                $day,
                $displayDay,
                $businessDaysInPeriod,
                $amountDays,
                $currentBusinessDays,
                $amountBrutoRaw,
                $amountBruto,
                $profitBruto
            );
        }

        $this->printFooter($input);
    }

    private function getLoopEndDate(DateTimeImmutable $redemptionDate, DateTimeImmutable $iofPeriodEnd): DateTimeImmutable
    {
        return $redemptionDate <= $iofPeriodEnd ? $redemptionDate : $iofPeriodEnd;
    }

    private function getDailyPercentage(InvestmentInput $input): float
    {
        return (float) $this->calculationInvestment->resolveDisplayPercentage($input, $this->rateService);
    }


    private function printHeader(): void
    {
        echo str_repeat('=', $this->tableWidth) . "\n";
        echo str_pad('Simulação diária (período de cobrança do IOF)', $this->tableWidth, ' ', STR_PAD_BOTH) . "\n";
        echo str_repeat('=', $this->tableWidth) . "\n";

        printf(
            "%-12s %6s %11s %9s %13s %14s %12s %14s\n",
            'Data', 'Dias', 'Dias Úteis', '% Mês', 'Bruto', 'Lucro Bruto', 'IOF', 'Lucro Líq.'
        );

        echo str_repeat('-', $this->tableWidth) . "\n";
    }

    private function printFooter(InvestmentInput $input): void
    {
        echo str_repeat('-', $this->tableWidth) . "\n";

        if ($input->isIsento) {
            echo "Obs.: LCI/LCA são isentos de IR e IOF.\n";
        }

        echo str_repeat('=', $this->tableWidth) . "\n";
    }

    private function printDayRow(
        InvestmentInput $input,
        DateTimeImmutable $day,
        int $displayDay,
        int $businessDaysInPeriod,
        int $amountDays,
        int $currentBusinessDays,
        string $amountBrutoRaw,
        string $amountBruto,
        string $profitBruto
    ): void {

        if ($this->shouldSkipDay($day, $displayDay)) {
            return;
        }

        $IofValue     = $this->getIofValue($input, $profitBruto, $displayDay);
        $AmountLiquid = $this->getLiquidAmount($input, $amountBrutoRaw, $displayDay);
        $ProfitLiquid = $this->profitService->calculateProfitLiquid($input->initialCapital, $AmountLiquid);
        $ProfitLiquid = $this->formatterService->normalizeAmountRounded($ProfitLiquid);

        if (bccomp($ProfitLiquid, '0.00', 2) < 0) {
            $ProfitLiquid = '0.00';
        }

        $monthProgress = $this->getMonthProgress($displayDay, $currentBusinessDays, $businessDaysInPeriod);

        printf(
            "%-12s %6d %11d %9s %13s %14s %12s %14s\n",
            $day->format('d/m/Y'),
            $amountDays,
            $currentBusinessDays,
            number_format($monthProgress, 2, ',', '.') . '%',
            number_format((float) $amountBruto, 2, ',', '.'),
            number_format((float) $profitBruto, 2, ',', '.'),
            number_format((float) $IofValue, 2, ',', '.'),
            number_format((float) $ProfitLiquid, 2, ',', '.')
        );
    }

    private function shouldSkipDay(DateTimeImmutable $day, int $displayDay): bool
    {
        return $displayDay > 0 && ! $this->businessDayService->isBusinessDay($day->format('Y-m-d'));
    }

    private function getIofValue(InvestmentInput $input, string $ProfitBruto, int $displayDay): string
    {
        if ($input->isIsento) {
            return '0.00';
        }

        return $this->formatterService->normalizeAmount(
            $this->taxService->calculateIOFValue($ProfitBruto, $displayDay)
        );
    }

    private function getLiquidAmount(InvestmentInput $input, string $AmountRaw, int $displayDay): string
    {
        if ($input->isIsento) {
            return $AmountRaw;
        }

        return $this->taxService->calculateIR($input->initialCapital, $AmountRaw, $displayDay, false);
    }

    private function getMonthProgress(int $displayDay, int $currentBusinessDays, int $businessDaysInPeriod): float
    {
        if ($displayDay >= 30) {
            return 100.0;
        }

        if ($businessDaysInPeriod <= 0) {
            return 0.0;
        }

        return min(round(($currentBusinessDays / $businessDaysInPeriod) * 100, 2), 100.0);
    }

}
