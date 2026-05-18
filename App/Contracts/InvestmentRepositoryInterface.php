<?php

namespace App\Contracts;

use App\ValueObjects\InvestmentInput;
use App\ValueObjects\Investment;

interface InvestmentRepositoryInterface
{
    public function save(InvestmentInput $input, Investment $result): Investment;
}
