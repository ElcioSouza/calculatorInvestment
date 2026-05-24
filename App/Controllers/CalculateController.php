<?php

namespace App\Controllers;

use App\Console\ConsoleInput;
use App\Factories\InvestmentInputFactory;
use App\Services\CdiRateService;
use App\UseCases\CalculateInvestmentUseCase;
use App\ValueObjects\Investment;

class CalculateController
{
    public function __construct(
        private InvestmentInputFactory $investmentInputFactory,
        private CalculateInvestmentUseCase $calculateInvestmentUseCase,
        private CdiRateService $cdiRateService,
    ) {}

    public function execute(array $argv): array
    {
        $defaultSelic = $this->cdiRateService->fetchSelicAnnual('14.40');
        ConsoleInput::showInvestmentDefaults($defaultSelic);

        $investmentInput = $this->investmentInputFactory->create($argv, $defaultSelic);

        $result = $this->calculateInvestmentUseCase->execute($investmentInput);

        return [
            'input' => $investmentInput,
            'result' => $result,
        ];
    }
}