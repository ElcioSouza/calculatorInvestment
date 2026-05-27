<?php

namespace App\Repositories;

use PDO;

class DeleteInvestmentRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function deleteInvestment(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM investments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
