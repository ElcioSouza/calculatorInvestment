<?php

namespace App\Presenters;

use App\ValueObjects\InvestmentInput;
use App\ValueObjects\Investment;

abstract class AbstractInvestmentPresenter
{
    protected int $width;

    public function __construct(int $width = 60)
    {
        $this->width = $width;
    }

    protected function renderHeader(string $rateType): void
    {
        echo "\n" . str_repeat('=', $this->width) . "\n";
        echo str_pad(
            'Cálculo de investimento ' . $rateType . ' - CDB, LCI, LCA',
            $this->width,
            ' ',
            STR_PAD_BOTH
        ) . "\n";
        echo str_repeat('=', $this->width) . "\n";
    }

    protected function renderInvestmentDetails(
        InvestmentInput $input,
        string $rateType,
        string $taxaSelicAtual,
        callable $money,
        int $businessDaysPerMonth
    ): void {
        $mNormal = "%-40s %20s\n";
        $mAcento = "%-40s %21s\n";

        printf($mNormal, 'Tipo de investimento:', $input->investmentType);
        printf($mAcento, 'Tipo da taxa:', strtoupper($rateType));
        printf($mNormal, 'Capital Inicial:', $money($input->initialCapital));

        if ($input->rateType === 'pre') {
            printf($mNormal, 'Rentabilidade:', $input->preFixedAnnualRate . '% a.a.');
        } else {
            printf($mNormal, 'Rentabilidade:', $input->cdiPercentage . '% do CDI');
            printf($mNormal, 'Taxa Selic atual:', $taxaSelicAtual . '%');
        }

      
        if($input->applicationDate) {
            $m01Acento = "%-41s %21s\n";
            printf($m01Acento, 'Data de Aplicação:', date('d/m/Y', strtotime($input->applicationDate)));
        }
        printf($mNormal, 'Data de Resgate:', date('d/m/Y', strtotime($input->redemptionDate)));
        printf($mNormal, 'Prazo:', $this->formatPrazo($input->months));
        if($businessDaysPerMonth) {
            $m01Acento = "%-41s %21s\n";
            printf($m01Acento, 'Dias Úteis por mês (Base 252):', (string) $businessDaysPerMonth ." Dias");
        }

        echo str_repeat('-', $this->width) . "\n";
    }

    protected function renderGrossSection(Investment $result, callable $money): void
    {
        $mNormal = "%-40s %20s\n";

        printf($mNormal, 'Lucro Bruto:', $money($result->profitBruto));
       
        printf($mNormal, 'Lucro Bruto por dia:', $money($result->dailyProfitDisplay));
        printf($mNormal, 'Montante Bruto:', $money($result->amountBruto));

        echo str_repeat('-', $this->width) . "\n";
    }

    protected function renderTaxSection(Investment $result, callable $money): void
    {
        $mNormal = "%-40s %20s\n";
        $mAcento = "%-39s %21s\n";

        printf(
            $mNormal,
            '(-) IOF recolhido:',
            $money($result->iofValue) . ($result->isIsento ? ' (Isento)' : ($result->days >= 30 ? ' (Isento)' : ''))
        );
        printf(
            $mAcento,
            '(-) Imposto de Renda (IR):',
            $money($result->irTaxAmount) . ($result->isIsento ? ' (Isento)' : '')
        );

        echo str_repeat('-', $this->width) . "\n";
    }

    protected function renderNetSection(Investment $result, callable $money): void
    {
        $mNormal = "%-40s %20s\n";
        $mAcento = "%-40s %21s\n";
        printf($mNormal, 'LUCRO LÍQUIDO:', $money($result->profitLiquid));
        printf($mAcento, 'Lucro Líquido por mês:', $money($result->monthlyProfitLiquid));
        printf(
            $mNormal,
            'Lucro Líquido por dia:',
            $money($result->businessDays > 0 ? bcdiv($result->profitLiquid, (string) $result->businessDays, 2) : '0.00')
        );

        echo str_repeat('-', $this->width) . "\n";

        printf($mNormal, 'MONTANTE LÍQUIDO FINAL:', $money($result->amountLiquid));
    }

    protected function renderFooter(): void
    {
        echo str_repeat('=', $this->width) . "\n\n";
    }

    protected function formatMoney(mixed $value): string
    {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }

    protected function resolveRateType(InvestmentInput $input): string
    {
        return strtolower(trim($input->rateType)) === 'pre' ? 'pré-fixado' : 'pós-fixado';
    }

    protected function resolveTaxaSelicAtual(InvestmentInput $input): string
    {
        return number_format((float) $input->selicMeta, 2, '.', '');
    }

    protected function resolveBusinessDaysPerMonth(InvestmentInput $input, Investment $result): int
    {
        return $input->months > 0 ? (int) round($result->businessDays / $input->months) : 0;
    }

    protected function formatPrazo(int $months): string
    {
        $years     = intdiv($months, 12);
        $remMonths = $months % 12;
        $prazoStr  = $months . ' Meses';

        if ($years > 0) {
            $prazoStr .= $remMonths > 0
                ? " ({$years} anos {$remMonths} meses)"
                : " ({$years} anos)";
        }

        return $prazoStr;
    }
}
