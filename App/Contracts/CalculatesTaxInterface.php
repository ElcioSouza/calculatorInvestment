<?php

namespace App\Contracts;

interface CalculatesTaxInterface
{
    public function calculateIR(string $initialCapital, string $amountBruto, int $days, bool $isIsento = false): string;
    public function calculateIOFValue(string $lucroBruto, int $days): string;
}
