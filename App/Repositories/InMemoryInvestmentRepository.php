<?php
namespace App\Repositories;

use App\Contracts\InvestmentRepositoryInterface;
use App\ValueObjects\Investment;
use App\ValueObjects\InvestmentInput;

class InMemoryInvestmentRepository implements InvestmentRepositoryInterface
{
    public function __construct(
        private array $storage = [],
        private int $nextId = 1
    ) {}

    public function save(InvestmentInput $input, Investment $result): int
    {
        $id = $this->nextId++;
        $this->storage[$id] = [
            'id'     => $id,
            'input'  => $input,
            'result' => $result,
        ];
        return $id;
    }

    public function all(): array
    {
        return array_values($this->storage);
    }

    public function getLast(): ?array
    {
        $all = $this->all();
        if (empty($all)) {
            return null;
        }
        return end($all);
    }

    public function findById(int|string $id): ?array
    {
        $id = (int) $id;
        return $this->storage[$id] ?? null;
    }

    public function update(int|string $id, InvestmentInput $input, Investment $result): int
    {
        $id = (int) $id;
        $this->storage[$id] = [
            'id'     => $id,
            'input'  => $input,
            'result' => $result,
        ];
        return $id;
    }

    public function delete(int|string $id): bool
    {
        $id = (int) $id;
        if (!isset($this->storage[$id])) {
            return false;
        }
        unset($this->storage[$id]);
        return true;
    }
}
