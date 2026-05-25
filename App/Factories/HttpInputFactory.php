<?php
namespace App\Factories;

use App\Services\CdiRateService;
use App\Services\RateCalculationService;
use App\ValueObjects\InvestmentInput;

final class HttpInputFactory extends BaseFactory
{
    public function __construct(
        private readonly CdiRateService $cdiRateService,
        private readonly RateCalculationService $rateCalculationService,
    ) {
    }

    public function create(array $params): InvestmentInput
    {
        $params = $this->normalizeParamAliases($params);

        $defaultDate = (new \DateTime())->format('Y-m-d');
        $defaultSelic = $this->cdiRateService->fetchSelicAnnual('14.40');

        $investmentType = $this->getParam($params, 'investment_type', 'cdb');
        $investmentType = $this->normalizeInvestmentType($investmentType);

        $rateType = $this->getParam($params, 'rate_type', 'pos');
        $rateType = $this->normalizeRateType($rateType);

        $applicationDateRaw = $this->getParam($params, 'application_date', $defaultDate);
        $applicationDate    = $this->normalizeDateOrFail(trim($applicationDateRaw));
        $this->ensureIsBusinessDay($applicationDate);

        $monthsRaw = $this->getParam($params, 'months', '1');
        $months    = $this->normalizePositiveIntegerOrFail(trim($monthsRaw), 'Prazo de investimento');

        $redemptionDate = $this->calculateRedemptionDateByMonths($applicationDate, (int) $months);

        $capitalRaw     = $this->getParam($params, 'capital', '10000');
        $initialCapital = $this->normalizePositiveNumberOrFail(trim($capitalRaw), 'Capital inicial');

        if ($rateType === 'pre') {
            $cdiPercentage      = $this->getParam($params, 'cdi', '');
            $selicMeta          = $this->getParam($params, 'selic_meta', '');
            $preRateRaw         = $this->getParam($params, 'pre_rate', '11.50');
            $preFixedAnnualRate = $this->normalizePositiveNumberOrFail(trim($preRateRaw), 'Taxa prefixada anual');
        } else {
            $cdiRaw        = $this->getParam($params, 'cdi', '100');
            $selicRaw      = $this->getParam($params, 'selic_meta', $defaultSelic);
            $cdiPercentage = $this->normalizePositiveNumberOrFail(trim($cdiRaw), 'Rentabilidade (% do CDI)');
            $selicMeta     = $this->normalizePositiveNumberOrFail(trim($selicRaw), 'Selic Meta');
            $preRateRaw    = $this->getParam($params, 'pre_rate', '');
            $preFixedAnnualRate = $preRateRaw !== ''
                ? $this->normalizePositiveNumberOrFail(trim($preRateRaw), 'Taxa prefixada anual')
                : '';
        }
        
        $cdiOver   = '';
        $manualCdiAnnual = $this->getParam($params, 'cdi_annual', '');
        if ($manualCdiAnnual !== '') {
            $cdiOver = $this->normalizePositiveNumberOrFail(trim($manualCdiAnnual), 'CDI anual manual');
        } elseif (array_key_exists('selic_meta', $params)) {
            $cdiOver = $this->rateCalculationService->convertSelicMetaToOver($selicMeta);
        } else {
            $cdiResult = $this->cdiRateService->fetchCdiAnnual($selicMeta);
            $cdiOver   = $cdiResult['rate'];
        }

        return new InvestmentInput(
            initialCapital: $initialCapital,
            investmentType: $investmentType,
            rateType: $rateType,
            cdiPercentage: $cdiPercentage,
            selicMeta: $selicMeta,
            preFixedAnnualRate: $preFixedAnnualRate,
            selicIsOver: false,
            applicationDate: $applicationDate,
            redemptionDate: $redemptionDate,
            months: (int) $months,
            cdiOver: $cdiOver,
        );
    }

    private function normalizeParamAliases(array $params): array
    {
        $aliases = [
            'initial_capital' => 'capital',
            'cdi_percentage'  => 'cdi',
            'pre_fixed_rate'  => 'pre_rate',
            'cdi_over'        => 'cdi_annual',
        ];
        foreach ($aliases as $alias => $internal) {
            if (array_key_exists($alias, $params) && $params[$alias] !== '' && $params[$alias] !== null) {
                $params[$internal] = $params[$alias];
            }
        }
        return $params;
    }

    private function getParam(array $params, string $key, string $default): string
    {
        $value = $params[$key] ?? $default;
        return $value === '' ? $default : (string) $value;
    }

    private function normalizeRateType(string $value): string
    {
        $value = strtolower(trim($value));
        return match ($value) {
            '1', 'pre', 'pré' => 'pre',
            '2', 'pos', 'pós', 'post' => 'pos',
            default => $value,
        };
    }

    public function inputToParams(\App\ValueObjects\InvestmentInput $input): array
    {
        return [
            'investment_type'  => $input->investmentType,
            'rate_type'        => $input->rateType,
            'application_date' => $input->applicationDate,
            'months'           => (string) $input->months,
            'capital'          => $input->initialCapital,
            'cdi'              => $input->cdiPercentage,
            'selic_meta'       => $input->selicMeta,
            'pre_rate'         => $input->preFixedAnnualRate,
            'cdi_annual'       => $input->cdiOver,
        ];
    }

    private function normalizeInvestmentType(string $value): string
    {
        $value = strtolower(trim($value));
        return match ($value) {
            '1', 'cdb' => 'cdb',
            '2', 'lci' => 'lci',
            '3', 'lca' => 'lca',
            default => $value,
        };
    }
}
