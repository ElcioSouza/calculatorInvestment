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

    public function execute(array $argv): array
    {
        ConsoleInput::showInvestmentDefaults();

        $investmentInput = $this->investmentInputFactory->create($argv);

        $result = $this->calculateInvestmentUseCase->execute($investmentInput);

        return [
            'input' => $investmentInput,
            'result' => $result,
        ];
    }
}