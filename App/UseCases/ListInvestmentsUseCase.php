<?php

namespace App\UseCases;

use App\Contracts\InvestmentRepositoryInterface;

class ListInvestmentsUseCase
{
    public function __construct(private InvestmentRepositoryInterface $repository) {}

    public function execute(): array
    {
        return $this->repository->all();
    }
}
