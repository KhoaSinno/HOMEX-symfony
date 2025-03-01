<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function findByPatient(User $patient): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('a.appointmentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findByDoctor(User $doctor): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.doctor = :doctor')
            ->setParameter('doctor', $doctor)
            ->orderBy('a.appointmentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findInvoices($patient)
    {
        return $this->createQueryBuilder('a')
            ->where('a.patient = :patient')
            ->andWhere('a.status = :confirmed')
            ->andWhere('a.paymentStatus = :paid')
            ->setParameter('patient', $patient)
            ->setParameter('confirmed', 'confirmed')
            ->setParameter('paid', 'paid')
            ->orderBy('a.appointmentDate', 'DESC') // Sắp xếp theo ngày mới nhất
            ->getQuery()
            ->getResult();
    }


    public function countAppointmentsByDoctorAndTime(User $doctor, \DateTime $date, string $timeSlot): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.doctor = :doctor')
            ->andWhere('a.appointmentDate = :date')
            ->andWhere('a.appointmentTime = :timeSlot')
            ->setParameter('doctor', $doctor)
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('timeSlot', $timeSlot)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Lấy số bệnh nhân đã đặt theo từng khung giờ
    public function getBookedPatientsByDoctorAndDate(User $doctor, \DateTime $date): array
    {
        $query = $this->createQueryBuilder('a')
            ->select('a.appointmentTime, COUNT(a.id) as bookedCount')
            ->where('a.doctor = :doctor')
            ->andWhere('a.appointmentDate = :date')
            ->groupBy('a.appointmentTime')
            ->setParameter('doctor', $doctor)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery();

        $results = $query->getResult();

        // Chuyển kết quả thành mảng key-value
        $bookedSlots = [];
        foreach ($results as $result) {
            $bookedSlots[$result['appointmentTime']] = (int) $result['bookedCount'];
        }

        return $bookedSlots;
    }

    // Tổng doanh thu toàn bộ
    public function getTotalRevenue(): float
    {
        return $this->createQueryBuilder('a')
            ->select('SUM(a.price)')
            ->where('a.status = :confirmed')
            ->andWhere('a.paymentStatus = :paid')
            ->setParameter('confirmed', 'confirmed')
            ->setParameter('paid', 'paid')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    // Tổng doanh thu theo bác sĩ
    public function getTotalRevenueByDoctor(User $doctor): float
    {
        return $this->createQueryBuilder('a')
            ->select('SUM(a.price)')
            ->where('a.doctor = :doctor')
            ->andWhere('a.status = :confirmed')
            ->andWhere('a.paymentStatus = :paid')
            ->setParameter('doctor', $doctor)
            ->setParameter('confirmed', 'confirmed')
            ->setParameter('paid', 'paid')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
