<?php

namespace App\Contracts;

interface FormatsAmountInterface
{
    public function normalizeAmount(string $amount): string;
    public function normalizeAmountRounded(string $amount): string;
}
