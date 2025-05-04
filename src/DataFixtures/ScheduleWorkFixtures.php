<?php

namespace App\DataFixtures;

use App\Entity\ScheduleWork;
use App\Entity\User;
use App\Enum\ScheduleStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ScheduleWorkFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Lấy bác sĩ Doe từ reference đã được tạo trong UserFixtures
        $doctorDoe = $this->getReference('doctor-doe', User::class);
        
        // Tạo lịch làm việc cho ngày 05/05/2025
        $scheduleDay1 = new ScheduleWork();
        $scheduleDay1->setDoctor($doctorDoe);
        $scheduleDay1->setDate(new \DateTime('2025-05-05'));
        $scheduleDay1->setTimeSlots([
            "07:00-07:10",
            "07:10-07:20",
            "07:20-07:30",
            "07:30-07:40",
            "07:40-07:50",
            "08:00-08:10",
            "08:10-08:20",
            "08:20-08:30"
        ]);
        $scheduleDay1->setStatus(ScheduleStatus::AVAILABLE);
        $manager->persist($scheduleDay1);
        
        // Tạo lịch làm việc cho ngày 06/05/2025
        $scheduleDay2 = new ScheduleWork();
        $scheduleDay2->setDoctor($doctorDoe);
        $scheduleDay2->setDate(new \DateTime('2025-05-06'));
        $scheduleDay2->setTimeSlots([
            "14:00-14:10",
            "14:10-14:20",
            "14:20-14:30",
            "14:30-14:40",
            "14:40-14:50",
            "15:00-15:10",
            "15:10-15:20",
            "15:20-15:30"
        ]);
        $scheduleDay2->setStatus(ScheduleStatus::AVAILABLE);
        $manager->persist($scheduleDay2);
        
        // Tạo lịch làm việc cho ngày 07/05/2025 (đã đầy)
        $scheduleDay3 = new ScheduleWork();
        $scheduleDay3->setDoctor($doctorDoe);
        $scheduleDay3->setDate(new \DateTime('2025-05-07'));
        $scheduleDay3->setTimeSlots([
            "09:00-09:10",
            "09:10-09:20",
            "09:20-09:30",
            "09:30-09:40"
        ]);
        $scheduleDay3->setStatus(ScheduleStatus::AVAILABLE);
        $manager->persist($scheduleDay3);
        
        // Tạo lịch làm việc cho ngày 08/05/2025 (đã hủy)
        $scheduleDay4 = new ScheduleWork();
        $scheduleDay4->setDoctor($doctorDoe);
        $scheduleDay4->setDate(new \DateTime('2025-05-08'));
        $scheduleDay4->setTimeSlots([
            "10:00-10:10",
            "10:10-10:20",
            "10:20-10:30",
            "10:30-10:40",
            "10:40-10:50"
        ]);
        $scheduleDay4->setStatus(ScheduleStatus::AVAILABLE);
        $manager->persist($scheduleDay4);
        
        // Tạo lịch làm việc cách 1 tuần (ngày 12/05/2025)
        $scheduleDay5 = new ScheduleWork();
        $scheduleDay5->setDoctor($doctorDoe);
        $scheduleDay5->setDate(new \DateTime('2025-05-12'));
        $scheduleDay5->setTimeSlots([
            "07:00-07:10",
            "07:10-07:20",
            "07:20-07:30",
            "13:00-13:10",
            "13:10-13:20",
            "13:20-13:30",
            "16:00-16:10",
            "16:10-16:20",
            "16:20-16:30"
        ]);
        $scheduleDay5->setStatus(ScheduleStatus::AVAILABLE);
        $manager->persist($scheduleDay5);
        
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}