<?php

namespace App\UseCases;

use App\Services\ListInvestmentService;

class ListInvestmentsUseCase
{
    public function __construct(
        private ListInvestmentService $service,
    ) {}

    public function execute(): array
    {
        return $this->service->execute();
    }
}
