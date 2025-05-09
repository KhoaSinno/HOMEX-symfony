<?php

namespace App\Repository;

use App\Constants\AppointmentConstants;
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

    public function findAppointByPatient(User $patient): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.patient = :patient')
            ->andWhere('a.status IN (:statuses)')
            ->andWhere('a.paymentStatus = :paymentStatus')
            ->setParameter('patient', $patient)
            ->setParameter('statuses', ['pending', 'active'])
            ->setParameter('paymentStatus', 'paid')
            ->orderBy('a.appointmentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Tìm bệnh nhân theo ngày, bác sĩ và trạng thái đặt lịch
    public function findByDoctorAndDate(User $doctor, \DateTime $date, User $patient): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.doctor = :doctor')
            ->andWhere('a.patient = :patient')
            ->andWhere('a.appointmentDate = :date')
            ->andWhere('a.status = :pending')
            ->andWhere('a.paymentStatus = :paid')
            ->setParameter('doctor', $doctor)
            ->setParameter('patient', $patient)
            ->setParameter('date', $date->format('Y-m-d')) // Định dạng ngày đúng
            ->setParameter('pending', 'pending') // Nếu status là string, cần đặt giá trị cụ thể
            ->setParameter('paid', 'paid') // Nếu paymentStatus là string
            ->orderBy('a.appointmentTime', 'ASC')
            ->getQuery()
            ->getResult();
    }


    public function findByDoctor(User $doctor): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.doctor = :doctor')
            ->andWhere('a.status = :pending')
            ->andWhere('a.paymentStatus = :paid')
            ->setParameter('doctor', $doctor)
            ->setParameter('pending', AppointmentConstants::PENDING_STATUS)
            ->setParameter('paid', AppointmentConstants::PAID_STATUS)
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
            ->setParameter('confirmed', 'completed')
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
            ->where('a.status = :completed')
            ->andWhere('a.paymentStatus = :paid')
            ->setParameter('completed', 'completed')
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

    public function getSlotStatusesByDoctorAndDate(User $doctor, \DateTime $date): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.appointmentTime, a.status')
            ->where('a.doctor = :doctor')
            ->andWhere('a.appointmentDate = :date')
            ->setParameter('doctor', $doctor)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery();

        $appointments = $qb->getResult();

        $slotStatuses = [];
        foreach ($appointments as $appointment) {
            $slotStatuses[$appointment['appointmentTime']] = $appointment['status'];
        }

        return $slotStatuses;
    }
}
