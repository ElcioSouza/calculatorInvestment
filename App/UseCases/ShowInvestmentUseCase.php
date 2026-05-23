<?php

namespace App\UseCases;

use App\Contracts\InvestmentRepositoryInterface;

class ShowInvestmentUseCase
{
    public function __construct(private InvestmentRepositoryInterface $repository) {}

    public function execute(string $id): ?array
    {
        return $this->repository->findById($id);
    }
}
