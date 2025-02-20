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

    //    /**
    //     * @return Appointment[] Returns an array of Appointment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Appointment
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
