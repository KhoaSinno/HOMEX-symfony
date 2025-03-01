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
            new TwigFilter('gender_label', [$this, 'mappingGender']),
            new TwigFilter('specialty_label', [$this, 'mappingSpecialty']),

            // Status badge cho Appoinment
            new TwigFilter('convertAppointStatus', [$this, 'convertAppointStatus']),
            new TwigFilter('status_badge', [$this, 'statusBadge'], ['is_safe' => ['html']]),

            // Status badge cho Payment
            new TwigFilter('convertPaymentStatus', [$this, 'convertPaymentStatus']),
            new TwigFilter('payment_class', [$this, 'paymentClass', ['is_safe' => ['html']]]),
            new TwigFilter('payment_badge', [$this, 'paymentBadge'], ['is_safe' => ['html']]),
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
    public function mappingGender(string $gender): string
    {
        return match ($gender) {
            'Male' => 'Nam',
            'Female' => 'Nữ',
            default => 'Trống',
        };
    }
    public function mappingSpecialty(string $specialty): string
    {
        return match ($specialty) {
            'Urology' => 'Tiết niệu',
            'Neurology' => 'Thần kinh',
            'Orthopedic' => 'Chỉnh hình',
            'Cardiologist' => 'Tim mạch',
            'Dentist' => 'Nha khoa',
            default => 'Trống',
        };
    }
    // Status badge cho Appoinment
    public function statusBadge(string $status): string
    {
        return match ($status) {
            'pending' => '<span class="badge badge-pill bg-warning-light">Chờ xác nhận</span>',
            'cancelled' => '<span class="badge badge-pill bg-danger-light">Đã hủy</span>',
            'confirmed' => '<span class="badge badge-pill bg-success-light">Xác nhận</span>',
            default => '<span class="badge badge-pill bg-secondary-light">Trống</span>',
        };
    }

    // Status badge cho Payment
    public function paymentClass(string $status)
    {
        return match ($status) {
            'paid' => 'bg-success',
            'unpaid' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function paymentBadge(string $status): string
    {
        return match ($status) {
            'paid' => '<span class="badge badge-pill bg-success-light">Đã thanh toán</span>',
            'unpaid' => '<span class="badge badge-pill bg-danger-light">Chưa thanh toán</span>',
            default => '<span class="badge badge-pill bg-secondary-light">Trống</span>',
        };
    }
}
