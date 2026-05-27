<?php

namespace App\Services;

use App\Contracts\InvestmentRepositoryInterface;
use App\Repositories\DeleteInvestmentRepository;

class DeleteInvestmentService
{
    public function __construct(
        private InvestmentRepositoryInterface $repository,
        private DeleteInvestmentRepository $deleteInvestmentRepository,
    ) {}

    public function execute(string $id): bool
    {
        $jsonDeleted  = $this->repository->delete($id);
        $mysqlDeleted = $this->deleteInvestmentRepository->deleteInvestment((int) $id);

        return $jsonDeleted || $mysqlDeleted;
    }
}
