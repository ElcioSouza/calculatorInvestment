<?php

namespace App\Controllers;

use App\UseCases\CalculateInvestmentUseCase;

/**
 * CalculateController
 *
 * Responsabilidade única: construir o InvestmentInput a partir do argv
 * e acionar o UseCase. Retorna input+result para o AppController
 * repassar aos demais controllers sem pedir dados novamente.
 */
class CalculateController
{
    public function __construct(
    ) {}

    public function execute(array $argv): array
    {
      return [];
    }
}