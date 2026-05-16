<?php

namespace App\Contracts;

use App\ValueObjects\InvestmentInput;
use App\ValueObjects\Investment;

interface InvestmentRepositoryInterface
{
    /**
     * Persiste/recupera o resultado de um cálculo de investimento.
     * Em implementações in-memory apenas devolve o resultado recebido.
     */
    public function save(InvestmentInput $input, Investment $result): Investment;
}
