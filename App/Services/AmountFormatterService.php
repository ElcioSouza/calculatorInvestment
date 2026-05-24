<?php
namespace App\Services;

use App\Contracts\FormatsAmountInterface;

class AmountFormatterService extends ServiceBase implements FormatsAmountInterface
{
    public function __construct(private int $precision = self::DEFAULT_PRECISION) {}

    public function normalizeAmount(string $amount): string
    {
        return sprintf('%.' . $this->precision . 'F', round((float) $amount, 2));
    }

    public function normalizeAmountRounded(string $amount): string
    {
        return $this->normalizeAmount($amount);
    }

    public function normalizeAmountTruncated(string $amount): string
    {
        return sprintf('%.' . $this->precision . 'F', floor((float) $amount * 100) / 100);
    }
}
