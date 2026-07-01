<?php
namespace App\Services;

use App\Contracts\CalculatesProfitInterface;
use App\Contracts\CalculatesRateInterface;
use App\Contracts\CalculatesTaxInterface;
use App\Contracts\CountsBusinessDaysInterface;
use App\Contracts\FormatsAmountInterface;
use App\Contracts\InvestmentRepositoryInterface;
use App\Helpers\InvestmentCalculationHelper as InvestmentCalculation;
use App\Repositories\CreateInvestmentRepository;
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
        private CreateInvestmentRepository $createInvestmentRepository,
    ) {}

    private ?int $lastSavedId = null;
    private ?int $lastId = null;

    public function handle(InvestmentInput $input): Investment
    {
        $result = $this->process($input);

        $this->lastId = $this->createInvestmentRepository->insertInvestment([
            'initial_capital'       => $input->initialCapital,
            'investment_type'       => $input->investmentType,
            'rate_type'             => $input->rateType,
            'cdi_percentage'        => $input->cdiPercentage !== '' ? $input->cdiPercentage : '0',
            'selic_meta'            => $input->selicMeta !== '' ? $input->selicMeta : '0',
            'selic_meta_default'    => $input->selicMetaDefault !== '' ? $input->selicMetaDefault : $input->selicMeta,
            'pre_fixed_annual_rate' => $input->preFixedAnnualRate !== '' ? $input->preFixedAnnualRate : '0',
            'application_date'      => $input->applicationDate,
            'redemption_date'       => $input->redemptionDate,
            'months'                => $input->months,
            'selic_is_over'         => $input->selicIsOver,
            'cdi_over'              => $input->cdiOver,
        ]);

        $this->createInvestmentRepository->insertEstimate($this->lastId, [
            'amount_bruto'          => $result->amountBruto,
            'amount_liquid'         => $result->amountLiquid,
            'profit_bruto'          => $result->profitBruto,
            'profit_liquid'         => $result->profitLiquid,
            'iof_value'             => $result->iofValue,
            'ir_tax_amount'         => $result->irTaxAmount,
            'monthly_profit_liquid' => $result->monthlyProfitLiquid,
            'daily_profit_display'  => $result->dailyProfitDisplay,
            'is_isento'             => $result->isIsento,
            'days'                  => $result->days,
            'business_days'         => $result->businessDays,
            'ir_aliquot'            => $result->irAliquot,
            'profit_percentage'     => $result->profitPercentage,
        ]);

        $this->lastSavedId = $this->repository->save($input, $result, $this->lastId);

        return $result;
    }

    public function getLastSavedId(): ?int
    {
        return $this->lastSavedId;
    }

    public function getLastId(): ?int
    {
        return $this->lastId;
    }

    public function recalculateUpdate(int|string $id, InvestmentInput $input): Investment
    {
        $result = $this->recalculate($input);

        $this->repository->update($id, $input, $result);

        $this->createInvestmentRepository->updateInvestment((int) $id, [
            'initial_capital'       => $input->initialCapital,
            'investment_type'       => $input->investmentType,
            'rate_type'             => $input->rateType,
            'cdi_percentage'        => $input->cdiPercentage !== '' ? $input->cdiPercentage : '0',
            'selic_meta'            => $input->selicMeta !== '' ? $input->selicMeta : '0',
            'selic_meta_default'    => $input->selicMetaDefault !== '' ? $input->selicMetaDefault : $input->selicMeta,
            'pre_fixed_annual_rate' => $input->preFixedAnnualRate !== '' ? $input->preFixedAnnualRate : '0',
            'application_date'      => $input->applicationDate,
            'redemption_date'       => $input->redemptionDate,
            'months'                => $input->months,
            'selic_is_over'         => $input->selicIsOver,
            'cdi_over'              => $input->cdiOver,
        ]);

        $this->createInvestmentRepository->updateEstimate((int) $id, [
            'amount_bruto'          => $result->amountBruto,
            'amount_liquid'         => $result->amountLiquid,
            'profit_bruto'          => $result->profitBruto,
            'profit_liquid'         => $result->profitLiquid,
            'iof_value'             => $result->iofValue,
            'ir_tax_amount'         => $result->irTaxAmount,
            'monthly_profit_liquid' => $result->monthlyProfitLiquid,
            'daily_profit_display'  => $result->dailyProfitDisplay,
            'is_isento'             => $result->isIsento,
            'days'                  => $result->days,
            'business_days'         => $result->businessDays,
            'ir_aliquot'            => $result->irAliquot,
            'profit_percentage'     => $result->profitPercentage,
        ]);

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

        $businessDaysInMonth = $this->businessDayService->countBusinessDaysInMonth(
            (int) $redemptionDT->format('Y'),
            (int) $redemptionDT->format('n')
        );

        $monthlyProfitLiquid = $input->isIsento
            ? bcdiv(bcmul($profitBrutoRaw, (string) $businessDaysInMonth, 6), (string) $businessDays, 2)
            : bcdiv(bcmul($profitLiquid, (string) $businessDaysInMonth, 6), (string) $businessDays, 2);

        $irTaxAmount = $input->isIsento
            ? '0.00'
            : $this->formatter->normalizeAmount(
            bcsub(bcsub($profitBrutoRaw, $iofRaw, 6), $profitLiquid, 6)
        );

        $irAliquot = $input->isIsento
            ? '0'
            : match (true) {
                $days <= 180 => '22.5',
                $days <= 360 => '20',
                $days <= 720 => '17.5',
                default      => '15',
            };

        $profitPercentageRaw = bccomp($input->initialCapital, '0', 6) > 0
            ? bcmul(bcdiv($profitBrutoRaw, $input->initialCapital, 6), '100', 6)
            : '0';
        $profitPercentage = number_format((float) $profitPercentageRaw, 2, '.', '');

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
            irAliquot: $irAliquot,
            profitPercentage: $profitPercentage,
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
