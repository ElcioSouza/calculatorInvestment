<?php

namespace App\ValueObjects;

final class InvestmentInput
{
    public readonly bool $isIsento;

    public function __construct(
        public readonly string $initialCapital,
        public readonly string $investmentType,
        public readonly string $rateType,
        public readonly string $cdiPercentage,
        public readonly string $selicMeta,
        public readonly string $preFixedAnnualRate,
        public readonly string $applicationDate,
        public readonly string $redemptionDate,
        public readonly int    $months,
        public readonly bool   $selicIsOver = false,
        public readonly string $cdiOver = '',
    ) {
        $this->isIsento = strtoupper($this->investmentType) !== 'CDB';
    }
}