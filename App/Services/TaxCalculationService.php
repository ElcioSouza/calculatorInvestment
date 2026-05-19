<?php

namespace App\Services;

use App\Contracts\CalculatesTaxInterface;

class TaxCalculationService extends ServiceBase implements CalculatesTaxInterface
{
    private array $iofTable;

    public function __construct(
        private int $scale = self::DEFAULT_SCALE,
        ?array $iofTable = null
    ) {
        $this->iofTable = $iofTable ?? self::IOF_TABLE;
    }

    public function calculateIR(string $initialCapital, string $amountBruto, int $days, bool $isIsento = false): string
    {
        $lucroBruto = bcsub($amountBruto, $initialCapital, $this->scale);

        if ($isIsento) {
            return bcadd($initialCapital, $lucroBruto, $this->scale);
        }

        $iofValue         = $this->calculateIOFValue($lucroBruto, $days);
        $baseTributavelIR = bcsub($lucroBruto, $iofValue, $this->scale);

        if (bccomp($baseTributavelIR, '0', $this->scale) <= 0) {
            return $initialCapital;
        }

        $aliquot = match (true) {
            $days <= 180 => '0.225',
            $days <= 360 => '0.20',
            $days <= 720 => '0.175',
            default      => '0.15',
        };

        $totalDescontoIR = bcmul($baseTributavelIR, $aliquot, $this->scale);
        $lucroLiquido    = bcsub($baseTributavelIR, $totalDescontoIR, $this->scale);

        return bcadd($initialCapital, $lucroLiquido, $this->scale);
    }

    public function calculateIOFValue(string $lucroBruto, int $days): string
    {
        if ($days > 30 || $days <= 0) {
            return '0.000000';
        }

        $aliquot = $this->iofTable[$days] ?? '0.00';
        return bcmul($lucroBruto, $aliquot, $this->scale);
    }
}
