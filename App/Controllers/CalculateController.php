<?php

namespace App\Controllers;

use App\Factories\InvestmentInputFactory;
use App\UseCases\CalculateInvestmentUseCase;
use App\ValueObjects\Investment;
use App\Console\ConsoleInput;
class CalculateController
{
    public function __construct(
        private InvestmentInputFactory $investmentInputFactory,
        private CalculateInvestmentUseCase $calculateInvestmentUseCase
    ) {}

    public function execute(array $argv): Investment
    {
        ConsoleInput::showInvestmentDefaults();
        
        $investmentInput = $this->investmentInputFactory->create($argv);

        return $this->calculateInvestmentUseCase->execute($investmentInput);
    }
}