<?php

namespace App\Services;

use App\Contracts\InvestmentRepositoryInterface;
use App\Repositories\ShowInvestmentRepository;

class ShowInvestmentService
{
    public function __construct(
        private InvestmentRepositoryInterface $jsonRepository,
        private ShowInvestmentRepository $mysqlRepository,
    ) {}

    public function execute(string $id): ?array
    {
        try {
            $item = $this->mysqlRepository->execute($id);
            if ($item !== null) {
                return $item;
            }
        } catch (\Throwable) {
        }

        return $this->jsonRepository->findById($id);
    }
}
