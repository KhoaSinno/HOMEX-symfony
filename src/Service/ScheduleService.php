<?php

namespace App\Service;

class ScheduleService
{
    public function generateTimeSlots(string $startTime, string $endTime, int $intervalMinutes): array
    {
        $timeSlots = [];
        $currentTime = new \DateTime($startTime);
        $endTimeObj = new \DateTime($endTime);

        // Khung giờ nghỉ trưa
        $breakStart = new \DateTime('12:00');
        $breakEnd = new \DateTime('13:30');

        while ($currentTime < $endTimeObj) {
            $slotStart = clone $currentTime;
            $slotEnd = (clone $currentTime)->modify("+$intervalMinutes minutes");

            // Bỏ qua khung giờ nghỉ trưa
            if (($slotStart >= $breakStart && $slotStart < $breakEnd) || ($slotEnd > $breakStart && $slotEnd <= $breakEnd)) {
                $currentTime = clone $breakEnd; // Nhảy đến 13:30
                continue;
            }

            $timeSlots[] = $slotStart->format('H:i') . '-' . $slotEnd->format('H:i');
            $currentTime = $slotEnd;
        }

        return $timeSlots;
    }
}
