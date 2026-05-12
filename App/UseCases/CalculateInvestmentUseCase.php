<?php

namespace App\UseCases;

use App\ValueObjects\InvestmentInput;
use App\ValueObjects\InvestmentResult;

class CalculateInvestmentUseCase
{
    public function __construct() {}

    public function execute(InvestmentInput $input): InvestmentResult
    {
       return [
        'initialAmount' => $input->amount ?? 1000,
        'finalAmount'   => ($input->amount ?? 1000) * 1.05,
        'interestRate'  => 0.05,
        'periods'       => $input->periods ?? 1,
    ];
    }
}
