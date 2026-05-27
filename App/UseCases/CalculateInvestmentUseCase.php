<?php

namespace App\UseCases;

use App\Services\InvestmentService;
use App\ValueObjects\InvestmentInput;
use App\ValueObjects\Investment;

class CalculateInvestmentUseCase
{
    public function __construct(
        private InvestmentService $service,
    ) {}

    public function execute(InvestmentInput $input): Investment
    {
        return $this->service->handle($input);
    }

    public function recalculate(InvestmentInput $input): Investment
    {
        return $this->service->recalculate($input);
    }

    public function recalculateAndUpdate(int|string $id, InvestmentInput $input): Investment
    {
        return $this->service->recalculateAndUpdate($id, $input);
    }

    public function getLastSavedId(): ?int
    {
        return $this->service->getLastSavedId();
    }

    public function getLastId(): ?int
    {
        return $this->service->getLastId();
    }
}
