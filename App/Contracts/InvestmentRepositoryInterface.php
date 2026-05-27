<?php

namespace App\Contracts;

use App\ValueObjects\InvestmentInput;
use App\ValueObjects\Investment;

interface InvestmentRepositoryInterface
{
    public function save(InvestmentInput $input, Investment $result, ?int $id = null): int;

    public function all(): array;

    public function getLast(): ?array;

    public function findById(int|string $id): ?array;

    public function update(int|string $id, InvestmentInput $input, Investment $result): int;

    public function delete(int|string $id): bool;
}
