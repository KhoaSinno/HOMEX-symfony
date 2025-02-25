<?php

namespace App\Repository;

use App\Entity\ScheduleWork;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduleWork>
 */
class ScheduleWorkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleWork::class);
    }
    public function getAvailableDatesByDoctor(User $doctor): array
{
    if (!$doctor || !$doctor->getId()) {
        throw new \InvalidArgumentException('Invalid doctor or missing ID');
    }

    $dates = $this->createQueryBuilder('s')
        ->select('s.date')
        ->where('s.doctor = :doctor')
        ->setParameter('doctor', $doctor)
        ->groupBy('s.date')
        ->orderBy('s.date', 'ASC')
        ->getQuery()
        ->getResult();

    return array_map(fn($d) => $d['date']->format('Y-m-d'), $dates);
}



public function getTimeSlotsByDoctorAndDate(User $doctor, \DateTime $date): array
{
    $result = $this->createQueryBuilder('s')
        ->select('s.timeSlots')
        ->where('s.doctor = :doctor')
        ->andWhere('s.date = :date')
        ->setParameter('doctor', $doctor)
        ->setParameter('date', $date->format('Y-m-d'))
        ->getQuery()
        ->getOneOrNullResult();

    return $result && isset($result['timeSlots']) ? $result['timeSlots'] : [];
}









    //    /**
    //     * @return ScheduleWork[] Returns an array of ScheduleWork objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ScheduleWork
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
