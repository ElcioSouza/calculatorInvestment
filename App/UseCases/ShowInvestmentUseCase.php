<?php

namespace App\UseCases;

use App\Services\ShowInvestmentService;

class ShowInvestmentUseCase
{
    public function __construct(
        private ShowInvestmentService $service,
    ) {}

    public function execute(string $id): ?array
    {
        return $this->service->execute($id);
    }
}
