<?php

namespace App\Constants;

class AppointmentConstants
{
    public const FOR_WHO_SELF = 'self';
    public const FOR_WHO_RELATION = 'family';

    // Appointments status
    public const PENDING_STATUS = 'pending';
    public const CONFIRMED_STATUS = 'confirmed';
    public const CANCELLED_STATUS = 'cancelled';

    // Payment status
    public const UNPAID_STATUS = 'unpaid';
    public const PAID_STATUS = 'paid';

    public const FOR_WHO_LABELS = [
        self::FOR_WHO_SELF => 'Bản thân',
        self::FOR_WHO_RELATION => 'Người thân',
    ];

    public const APPOINTMENT_STATUS_LABELS = [
        self::PENDING_STATUS => 'Chờ xác nhận',
        self::CONFIRMED_STATUS => 'Đã xác nhận',
        self::CANCELLED_STATUS => 'Đã hủy',
    ];

    public const PAYMENT_STATUS_LABELS = [
        self::UNPAID_STATUS => 'Chưa thanh toán',
        self::PAID_STATUS => 'Đã thanh toán',
    ];

    public static function getLabel(string $key): string
    {
        return self::FOR_WHO_LABELS[$key] ?? $key;
    }

    public static function getAppointmentStatusLabel(string $key): string
    {
        return self::APPOINTMENT_STATUS_LABELS[$key] ?? $key;
    }

    public static function getPaymentStatusLabel(string $key): string
    {
        return self::PAYMENT_STATUS_LABELS[$key] ?? $key;
    }
}
