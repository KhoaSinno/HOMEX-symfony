<?php

namespace App\DataFixtures;

use App\Constants\AppointmentConstants;
use App\Entity\Appointment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class AppointmentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Lấy danh sách doctor và patient đã tạo trong UserDataFixture
        $doctor = $this->getReference('doctor-doe', User::class);
        $patient = $this->getReference('patient-mary', User::class);

        $appointment = new Appointment();
        $appointment->setDoctor($doctor);
        $appointment->setPatient($patient);
        $appointment->setAppointmentDate(new \DateTime('2025-04-20')); // Ngày hẹn giả lập
        $appointment->setAppointmentTime('14:00-14:30'); // Giờ hẹn giả lập
        $appointment->setNote('Khám sức khỏe tổng quát');
        $appointment->setPrice(500000); // Giá khám bệnh
        $appointment->setPatientFullname($patient->getFullname());
        $appointment->setPatientDateOfBirth($patient->getDateOfBirth());
        $appointment->setPatientPhoneNumber($patient->getPhoneNumber());
        $appointment->setPatientAddress($patient->getAddress());
        $appointment->setPatientGender($patient->getGender());
        $appointment->setPatientEmail($patient->getEmail());
        $appointment->setReason('Khám định kỳ');
        $appointment->setStatus(AppointmentConstants::PENDING_STATUS); // Trạng thái chờ xác nhận
        $appointment->setPaymentStatus(AppointmentConstants::UNPAID_STATUS); // Chưa thanh toán
        $appointment->setForWho(AppointmentConstants::FOR_WHO_SELF); // Dành cho chính bệnh nhân

        $manager->persist($appointment);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
