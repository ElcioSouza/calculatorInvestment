<?php

namespace App\Repositories;

use PDO;

class CreateInvestmentRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function insertInvestment(array $input): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO investments
                (initial_capital, investment_type, rate_type, cdi_percentage,
                 selic_meta, pre_fixed_annual_rate, application_date,
                 redemption_date, months, selic_is_over, cdi_over)
             VALUES
                (:initial_capital, :investment_type, :rate_type, :cdi_percentage,
                 :selic_meta, :pre_fixed_annual_rate, :application_date,
                 :redemption_date, :months, :selic_is_over, :cdi_over)"
        );

        $stmt->execute([
            ':initial_capital'       => $input['initial_capital'],
            ':investment_type'       => strtolower($input['investment_type']),
            ':rate_type'             => strtolower($input['rate_type']),
            ':cdi_percentage'        => $input['cdi_percentage'],
            ':selic_meta'            => $input['selic_meta'],
            ':pre_fixed_annual_rate' => $input['pre_fixed_annual_rate'],
            ':application_date'      => $input['application_date'],
            ':redemption_date'       => $input['redemption_date'],
            ':months'                => $input['months'],
            ':selic_is_over'         => $input['selic_is_over'] ? 1 : 0,
            ':cdi_over'              => $input['cdi_over'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateInvestment(int $id, array $input): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE investments SET
                initial_capital = :initial_capital,
                investment_type = :investment_type,
                rate_type = :rate_type,
                cdi_percentage = :cdi_percentage,
                selic_meta = :selic_meta,
                pre_fixed_annual_rate = :pre_fixed_annual_rate,
                application_date = :application_date,
                redemption_date = :redemption_date,
                months = :months,
                selic_is_over = :selic_is_over,
                cdi_over = :cdi_over
             WHERE id = :id"
        );

        $stmt->execute([
            ':id'                    => $id,
            ':initial_capital'       => $input['initial_capital'],
            ':investment_type'       => strtolower($input['investment_type']),
            ':rate_type'             => strtolower($input['rate_type']),
            ':cdi_percentage'        => $input['cdi_percentage'],
            ':selic_meta'            => $input['selic_meta'],
            ':pre_fixed_annual_rate' => $input['pre_fixed_annual_rate'],
            ':application_date'      => $input['application_date'],
            ':redemption_date'       => $input['redemption_date'],
            ':months'                => $input['months'],
            ':selic_is_over'         => $input['selic_is_over'] ? 1 : 0,
            ':cdi_over'              => $input['cdi_over'],
        ]);
    }

    public function updateEstimate(int $investmentId, array $result): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE investment_estimate SET
                amount_bruto = :amount_bruto,
                amount_liquid = :amount_liquid,
                profit_bruto = :profit_bruto,
                profit_liquid = :profit_liquid,
                iof_value = :iof_value,
                ir_tax_amount = :ir_tax_amount,
                monthly_profit_liquid = :monthly_profit_liquid,
                daily_profit_display = :daily_profit_display,
                is_isento = :is_isento,
                days = :days,
                business_days = :business_days
             WHERE investment_id = :investment_id"
        );

        $stmt->execute([
            ':investment_id'         => $investmentId,
            ':amount_bruto'          => $result['amount_bruto'],
            ':amount_liquid'         => $result['amount_liquid'],
            ':profit_bruto'          => $result['profit_bruto'],
            ':profit_liquid'         => $result['profit_liquid'],
            ':iof_value'             => $result['iof_value'],
            ':ir_tax_amount'         => $result['ir_tax_amount'],
            ':monthly_profit_liquid' => $result['monthly_profit_liquid'],
            ':daily_profit_display'  => $result['daily_profit_display'],
            ':is_isento'             => $result['is_isento'] ? 1 : 0,
            ':days'                  => $result['days'],
            ':business_days'         => $result['business_days'],
        ]);
    }

    public function insertEstimate(int $investmentId, array $result): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO investment_estimate
                (investment_id, amount_bruto, amount_liquid, profit_bruto,
                 profit_liquid, iof_value, ir_tax_amount,
                 monthly_profit_liquid, daily_profit_display,
                 is_isento, days, business_days)
             VALUES
                (:investment_id, :amount_bruto, :amount_liquid, :profit_bruto,
                 :profit_liquid, :iof_value, :ir_tax_amount,
                 :monthly_profit_liquid, :daily_profit_display,
                 :is_isento, :days, :business_days)"
        );

        $stmt->execute([
            ':investment_id'         => $investmentId,
            ':amount_bruto'          => $result['amount_bruto'],
            ':amount_liquid'         => $result['amount_liquid'],
            ':profit_bruto'          => $result['profit_bruto'],
            ':profit_liquid'         => $result['profit_liquid'],
            ':iof_value'             => $result['iof_value'],
            ':ir_tax_amount'         => $result['ir_tax_amount'],
            ':monthly_profit_liquid' => $result['monthly_profit_liquid'],
            ':daily_profit_display'  => $result['daily_profit_display'],
            ':is_isento'             => $result['is_isento'] ? 1 : 0,
            ':days'                  => $result['days'],
            ':business_days'         => $result['business_days'],
        ]);
    }
}
