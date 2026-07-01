<?php

namespace App\Contracts;

interface CalculatesProfitInterface
{
    public function calculateProfitBruto(string $initialCapital, string $amountBruto): string;
    public function calculateProfitLiquid(string $initialCapital, string $amountLiquid): string;
    public function calculateDailyProfitLiquid(string $initialCapital, string $amountBruto, int $days, int $businessDays): string;
    public function calculateDailyProfitLiquidIsento(string $initialCapital, string $amountBruto, int $businessDays): string;
    public function calculateMonthlyProfitLiquid(string $initialCapital, string $amountBruto, int $days, int $businessDays, int $businessDaysInMonth): string;
    public function calculateMonthlyProfitLiquidIsento(string $initialCapital, string $amountBruto, int $businessDays, int $businessDaysInMonth): string;
}
