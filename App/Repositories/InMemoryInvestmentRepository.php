<?php
namespace App\Repositories;

use App\Contracts\InvestmentRepositoryInterface;
use App\ValueObjects\Investment;
use App\ValueObjects\InvestmentInput;

class InMemoryInvestmentRepository implements InvestmentRepositoryInterface
{

   public function __construct(private array $storage = []) {}

    public function save(InvestmentInput $input, Investment $result): Investment
    {
        $this->storage[] = ['input' => $input, 'result' => $result];
        return $result;
    }

    public function all(): array
    {
        return $this->storage;
    }
}
