<?php

namespace App\UseCases;

use App\Contracts\InvestmentRepositoryInterface;

class DeleteInvestmentUseCase
{
    public function __construct(private InvestmentRepositoryInterface $repository) {}

    public function execute(string $id): bool
    {
        return $this->repository->delete($id);
    }
}
