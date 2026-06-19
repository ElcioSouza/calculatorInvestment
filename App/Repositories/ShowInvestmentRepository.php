<?php
namespace App\Repositories;

use App\ValueObjects\Investment;
use App\ValueObjects\InvestmentInput;
use PDO;

class ShowInvestmentRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function execute(int|string $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                i.id,
                i.initial_capital,
                i.investment_type,
                i.rate_type,
                i.cdi_percentage,
                i.selic_meta,
                i.selic_meta_default,
                i.pre_fixed_annual_rate,
                i.application_date,
                i.redemption_date,
                i.months,
                i.selic_is_over,
                i.cdi_over,
                e.amount_bruto,
                e.amount_liquid,
                e.profit_bruto,
                e.profit_liquid,
                e.iof_value,
                e.ir_tax_amount,
                e.monthly_profit_liquid,
                e.daily_profit_display,
                e.is_isento,
                e.days,
                e.business_days
             FROM investments i
             LEFT JOIN investment_estimate e ON e.investment_id = i.id
             WHERE i.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return [
            'id'     => (int) $row['id'],
            'input'  => new InvestmentInput(
                initialCapital:       (string) $row['initial_capital'],
                investmentType:       $row['investment_type'],
                rateType:             $row['rate_type'],
                cdiPercentage:        (string) $row['cdi_percentage'],
                selicMeta:            (string) $row['selic_meta'],
                selicMetaDefault:     (string) $row['selic_meta_default'],
                preFixedAnnualRate:   (string) $row['pre_fixed_annual_rate'],
                applicationDate:      $row['application_date'],
                redemptionDate:       $row['redemption_date'],
                months:               (int) $row['months'],
                selicIsOver:          (bool) $row['selic_is_over'],
                cdiOver:              $row['cdi_over'],
            ),
            'result' => new Investment(
                amountBruto:          (string) $row['amount_bruto'],
                amountLiquid:         (string) $row['amount_liquid'],
                profitBruto:          (string) $row['profit_bruto'],
                profitLiquid:         (string) $row['profit_liquid'],
                iofValue:             (string) $row['iof_value'],
                irTaxAmount:          (string) $row['ir_tax_amount'],
                monthlyProfitLiquid:  (string) $row['monthly_profit_liquid'],
                dailyProfitDisplay:   (string) $row['daily_profit_display'],
                isIsento:             (bool) $row['is_isento'],
                days:                 (int) $row['days'],
                businessDays:         (int) $row['business_days'],
            ),
        ];
    }
}
