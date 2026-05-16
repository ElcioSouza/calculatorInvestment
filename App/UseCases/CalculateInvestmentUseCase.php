<?php

namespace App\UseCases;

use App\Services\InvestmentService;
use App\ValueObjects\InvestmentInput;
use App\ValueObjects\Investment;

class CalculateInvestmentUseCase
{
    public function __construct(private InvestmentService $service) {}

    public function execute(InvestmentInput $input): Investment
    {
      return $this->service->handle($input);
    }
}
