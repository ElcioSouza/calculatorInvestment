<?php

namespace App\Presenters;

use App\ValueObjects\InvestmentInput;
use App\ValueObjects\Investment;

class InvestmentPresenter extends AbstractInvestmentPresenter
{
    public function __construct(int $width = 60)
    {
        parent::__construct($width);
    }

    public function display(InvestmentInput $input, Investment $result): void
    {
        $money = fn($value) => $this->formatMoney($value);
        $rateType = $this->resolveRateType($input);
        $taxaSelicAtual = $this->resolveTaxaSelicAtual($input);
        $businessDaysPerMonth = $this->resolveBusinessDaysPerMonth($input, $result);

        $this->renderHeader($rateType);
        $this->renderInvestmentDetails($input, $rateType, $taxaSelicAtual, $money, $businessDaysPerMonth);
        $this->renderGrossSection($result, $money);
        $this->renderTaxSection($result, $money);
        $this->renderNetSection($result, $money);
        $this->renderFooter();
    }
}
