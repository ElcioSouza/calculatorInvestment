<?php

namespace App\UseCases;

use App\Services\InvestmentService;
use App\ValueObjects\InvestmentInput;
use App\ValueObjects\Investment;

class CalculateInvestmentUseCase
{
    public function __construct() {}

    public function execute(InvestmentInput $input): Investment
    {
      return new Investment(
            amountBruto: '1000.00',
            amountLiquid: '1000.00',
            profitBruto: '0.00',
            profitLiquid: '0.00',
            iofValue: '0.00',
            irTaxAmount: '0.00',
            monthlyProfitLiquid: '0.00',
            dailyProfitDisplay: '0.00',
            isIsento: $input->isIsento,
            days: 0,
            businessDays: 0,
        );
    }
}
