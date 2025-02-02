<?php


namespace App\Enum;

enum ScheduleStatus: string
{
    case AVAILABLE = 'Available';
    case FULL = 'Full';
    case CANCELED = 'Canceled';

    // public static function getValues(): array
    // {
    //     return array_column(self::cases(), 'value');
    // }
    
}
