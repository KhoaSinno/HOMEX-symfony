<?php

namespace App\Twig;

use App\Constants\AppointmentConstants;
use Composer\XdebugHandler\Status;
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
            // Status badge cho Appoinment
            new TwigFilter('status_badge', [$this, 'statusBadge'], ['is_safe' => ['html']]),
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

    // Status badge cho Appoinment
    public function statusBadge(string $status): string
    {
        return match ($status) {
            'pending' => '<span class="badge badge-pill bg-warning-light">Chờ xác nhận</span>',
            'cancelled' => '<span class="badge badge-pill bg-danger-light">Đã hủy</span>',
            'confirmed' => '<span class="badge badge-pill bg-success-light">Xác nhận</span>',
            default => '<span class="badge badge-pill bg-secondary-light">Không xác định</span>',
        };
    }
}
