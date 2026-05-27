<?php

namespace App\UseCases;

use App\Services\DeleteInvestmentService;

class DeleteInvestmentUseCase
{
    public function __construct(
        private DeleteInvestmentService $service,
    ) {}

    public function execute(string $id): bool
    {
        return $this->service->execute($id);
    }
}
