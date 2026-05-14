<?php

namespace App\Contracts;

interface CountsBusinessDaysInterface
{
    public function countBusinessDays(string $startDate, string $endDate): int;
}