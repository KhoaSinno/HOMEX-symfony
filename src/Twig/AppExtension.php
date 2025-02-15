<?php

namespace App\Twig;

use App\Constants\AppointmentConstants;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('currency_vnd', [$this, 'formatCurrencyVND']),
            new TwigFilter('translateForWho', [$this, 'translateForWho']),
            new TwigFilter('convertAppointStatus', [$this, 'convertAppointStatus']),
            new TwigFilter('convertPaymentStatus', [$this, 'convertPaymentStatus']),
        ];
    }

    public function formatCurrencyVND(?int $amount): string
    {
        if ($amount === null) {
            return 'Trống';
        }

        return number_format($amount, 0, ',', '.') . 'đ';
    }

    public function translateForWho(string $value): string
    {
        return AppointmentConstants::getLabel($value);
    }

    public function convertAppointStatus(string $status): string
    {
        return AppointmentConstants::getAppointmentStatusLabel($status);
    }

    public function convertPaymentStatus(string $status): string
    {
        return AppointmentConstants::getPaymentStatusLabel($status);
    }
    
}
