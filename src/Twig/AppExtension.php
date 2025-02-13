<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('currency_vnd', [$this, 'formatCurrencyVND']),
        ];
    }

    public function formatCurrencyVND(?int $amount): string
    {
        if ($amount === null) {
            return 'Trống';
        }

        return number_format($amount, 0, ',', '.') . 'đ';
    }
}
