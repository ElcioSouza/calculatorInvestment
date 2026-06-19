<?php
namespace App\Controllers;

use App\ValueObjects\Investment;
use App\ValueObjects\InvestmentInput;

abstract class BaseApiController
{
    protected function jsonResponse(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function buildPayload(InvestmentInput $input, Investment $result): array
    {
        return [
            'input'  => [
                'investment_type'  => $input->investmentType,
                'rate_type'        => $input->rateType,
                'initial_capital'  => (float) $input->initialCapital,
                'cdi_percentage'   => $input->cdiPercentage !== '' ? (float) $input->cdiPercentage : null,
                'selic_meta'       => $input->selicMetaDefault !== '' ? (float) $input->selicMetaDefault : ($input->selicMeta !== '' ? (float) $input->selicMeta : null),
                'selic_meta_default' => $input->selicMetaDefault !== '' ? (float) $input->selicMetaDefault : null,
                'pre_fixed_rate'   => $input->preFixedAnnualRate !== '' ? (float) $input->preFixedAnnualRate : null,
                'cdi_over'         => $input->cdiOver !== '' ? (float) $input->cdiOver : null,
                'application_date' => $input->applicationDate,
                'redemption_date'  => $input->redemptionDate,
                'months'           => $input->months,
                'is_isento'        => $input->isIsento,
            ],
            'result' => [
                'amount_bruto'          => (float) $result->amountBruto,
                'amount_liquid'         => (float) $result->amountLiquid,
                'profit_bruto'          => (float) $result->profitBruto,
                'profit_liquid'         => (float) $result->profitLiquid,
                'iof_value'             => (float) $result->iofValue,
                'ir_tax_amount'         => (float) $result->irTaxAmount,
                'monthly_profit_liquid' => (float) $result->monthlyProfitLiquid,
                'daily_profit_display'  => (float) $result->dailyProfitDisplay,
                'days'                  => $result->days,
                'business_days'         => $result->businessDays,
                'is_isento'             => $result->isIsento,
            ],
        ];
    }
}
