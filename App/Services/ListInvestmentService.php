<?php

namespace App\Services;

use App\Contracts\InvestmentRepositoryInterface;
use App\Repositories\ListInvestmentRepository;

class ListInvestmentService
{
    public function __construct(
        private InvestmentRepositoryInterface $jsonRepository,
        private ListInvestmentRepository $mysqlRepository,
    ) {}

    public function execute(): array
    {
        try {
            $result = $this->mysqlRepository->execute();
            if (!empty($result)) {
                return $result;
            }
        } catch (\Throwable $e) {
            error_log('[ListInvestmentService] MySQL fallback: ' . $e->getMessage());
        }

        return $this->jsonRepository->all();
    }

    public function paginated(int $page, int $perPage): array
    {
        try {
            return $this->mysqlRepository->paginated($page, $perPage);
        } catch (\Throwable $e) {
            error_log('[ListInvestmentService] MySQL fallback: ' . $e->getMessage());
        }

        return $this->jsonRepository->paginated($page, $perPage);
    }
}
