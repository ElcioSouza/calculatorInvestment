<?php
namespace App\Repositories;

use App\ValueObjects\Investment;
use App\ValueObjects\InvestmentInput;
use PDO;

class ListInvestmentRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function execute(): array
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
                e.business_days,
                e.ir_aliquot,
                e.profit_percentage
             FROM investments i
             LEFT JOIN investment_estimate e ON e.investment_id = i.id
             ORDER BY i.id DESC"
        );
        $stmt->execute();

        return $this->rowsToItems($stmt->fetchAll());
    }

    private function rowsToItems(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->rowToItem($row);
        }
        return $result;
    }

    private function rowToItem(array $row): array
    {
        return [
            'id'     => (int) $row['id'],
            'input'  => new InvestmentInput(
                initialCapital:       (string) ($row['initial_capital'] ?? '0'),
                investmentType:       $row['investment_type'] ?? 'cdb',
                rateType:             $row['rate_type'] ?? 'pos',
                cdiPercentage:        (string) ($row['cdi_percentage'] ?? '100'),
                selicMeta:            (string) ($row['selic_meta'] ?? '0'),
                selicMetaDefault:     (string) ($row['selic_meta_default'] ?? '0'),
                preFixedAnnualRate:   (string) ($row['pre_fixed_annual_rate'] ?? '0'),
                applicationDate:      $row['application_date'] ?? date('Y-m-d'),
                redemptionDate:       $row['redemption_date'] ?? date('Y-m-d'),
                months:               (int) ($row['months'] ?? 1),
                selicIsOver:          (bool) ($row['selic_is_over'] ?? false),
                cdiOver:              $row['cdi_over'] ?? '',
            ),
            'result' => new Investment(
                amountBruto:          (string) ($row['amount_bruto'] ?? '0'),
                amountLiquid:         (string) ($row['amount_liquid'] ?? '0'),
                profitBruto:          (string) ($row['profit_bruto'] ?? '0'),
                profitLiquid:         (string) ($row['profit_liquid'] ?? '0'),
                iofValue:             (string) ($row['iof_value'] ?? '0'),
                irTaxAmount:          (string) ($row['ir_tax_amount'] ?? '0'),
                monthlyProfitLiquid:  (string) ($row['monthly_profit_liquid'] ?? '0'),
                dailyProfitDisplay:   (string) ($row['daily_profit_display'] ?? '0'),
                isIsento:             (bool) ($row['is_isento'] ?? false),
                days:                 (int) ($row['days'] ?? 0),
                businessDays:         (int) ($row['business_days'] ?? 0),
                irAliquot:            (string) ($row['ir_aliquot'] ?? '0'),
                profitPercentage:     (string) ($row['profit_percentage'] ?? '0'),
            ),
        ];
    }
}
