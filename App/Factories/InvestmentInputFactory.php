<?php
namespace App\Factories;

use App\Console\ConsoleInput;
use App\Services\CdiRateService;
use App\ValueObjects\InvestmentInput;

final class InvestmentInputFactory extends BaseFactory
{
    public function __construct(private readonly CdiRateService $cdiRateService)
    {
    }

    public function create(array $argv): InvestmentInput
    {
        $defaultDate = (new \DateTime())->format('Y-m-d');

        $investmentType = ConsoleInput::option($argv, 'investment-type', '');
        $investmentType = $investmentType !== ''
            ? ConsoleInput::normalizeInvestmentType($investmentType)
            : ConsoleInput::normalizeInvestmentType(
            ConsoleInput::askOption(
                "Investimento [1=CDB 2=LCI 3=LCA] (padrão: 1): ",
                ['1', '2', '3'],
                '1'
            )
        );

        $rateType = ConsoleInput::option($argv, 'rate-type', '');
        $rateType = $rateType !== ''
            ? ConsoleInput::normalizeRateType($rateType)
            : ConsoleInput::normalizeRateType(ConsoleInput::askOption("Taxa [1=pré 2=pós] (padrão: 2): ", ['1', '2'], '2'));

        $applicationDate = ConsoleInput::option($argv, 'application-date', '');
        $applicationDate = $applicationDate !== ''
            ? $this->normalizeDateOrFail(trim($applicationDate))
            : $this->askValidDate("Data de aplicação [{$defaultDate}]: ", $defaultDate);

        $months = ConsoleInput::option($argv, 'months', '');
        $months = $months !== ''
            ? $this->normalizePositiveIntegerOrFail(trim($months), 'Prazo de investimento')
            : $this->askPositiveInteger("Prazo de investimento (meses de calendário) [1]: ", '1', 'Prazo de investimento');

        $redemptionDate = $this->calculateRedemptionDateByMonths($applicationDate, (int) $months);

        $initialCapital = ConsoleInput::option($argv, 'capital', '');
        $initialCapital = $initialCapital !== ''
            ? $this->normalizePositiveNumberOrFail(trim($initialCapital), 'Capital inicial')
            : $this->askPositiveNumber("Capital inicial [42000]: ", '42000', 'Capital inicial');

        $cdiPercentage      = ConsoleInput::option($argv, 'cdi', '130');
        $selicMeta          = ConsoleInput::option($argv, 'selic-meta', '14.40');
        $preFixedAnnualRate = ConsoleInput::option($argv, 'pre-rate', '11.50');

        if ($rateType === 'pre') {
            $preFixedAnnualRate = ConsoleInput::option($argv, 'pre-rate', '');
            $preFixedAnnualRate = $preFixedAnnualRate !== ''
                ? $this->normalizePositiveNumberOrFail(trim($preFixedAnnualRate), 'Taxa prefixada anual')
                : $this->askPositiveNumber("Taxa prefixada anual (% a.a.) [11.50]: ", '11.50', 'Taxa prefixada anual');
        } else {
            $cdiPercentage = ConsoleInput::option($argv, 'cdi', '');
            $cdiPercentage = $cdiPercentage !== ''
                ? $this->normalizePositiveNumberOrFail(trim($cdiPercentage), 'Rentabilidade (% do CDI)')
                : $this->askPositiveNumber("Rentabilidade (% do CDI) [130]: ", '130', 'Rentabilidade (% do CDI)');

            $selicMeta = ConsoleInput::option($argv, 'selic-meta', '');
            $selicMeta = $selicMeta !== ''
                ? $this->normalizePositiveNumberOrFail(trim($selicMeta), 'Selic Meta')
                : $this->askPositiveNumber("Selic Meta [14.40]: ", '14.40', 'Selic Meta');
        }

        $cdiOver   = '';
        $cdiSource = '';
        if ($rateType !== 'pre') {
            $manualCdiAnnual = ConsoleInput::option($argv, 'cdi-annual', '');
            if ($manualCdiAnnual !== '') {
                $cdiOver = $this->normalizePositiveNumberOrFail(trim($manualCdiAnnual), 'CDI anual manual');
                $cdiSource = 'Manual';
                echo "CDI utilizado (manual): {$cdiOver}% a.a.\n";
            } else {
                $cdiResult  = $this->cdiRateService->fetchCdiAnnual($selicMeta);
                $cdiOver    = $cdiResult['rate'];
                $cdiSource  = $cdiResult['source'];
                echo "CDI utilizado: {$cdiOver}% a.a. [{$cdiSource}]\n";
            }
        }

        return new InvestmentInput(
            initialCapital:     $initialCapital,
            investmentType:     $investmentType,
            rateType:           $rateType,
            cdiPercentage:      $cdiPercentage,
            selicMeta:          $selicMeta,
            preFixedAnnualRate: $preFixedAnnualRate,
            selicIsOver:        false,
            applicationDate:    $applicationDate,
            redemptionDate:     $redemptionDate,
            months:             (int) $months,
            cdiOver:            $cdiOver,
        );
    }

}
