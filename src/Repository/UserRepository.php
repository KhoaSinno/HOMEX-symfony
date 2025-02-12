<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    // Phương thức tìm kiếm người dùng theo role
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.specialty', 's') // Join với Specialty
            ->addSelect('s') // Lấy cả Specialty
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"' . $role . '"%') // Tìm role trong mảng JSON
            ->getQuery()
            ->getResult();
    }

    public function findDoctorsByCriteria(array $criteria)
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DOCTOR%'); // Tìm kiếm chỉ những bác sĩ

        if (!empty($criteria['name'])) {
            $qb->andWhere('u.fullname LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['address'])) {
            $qb->andWhere('u.address LIKE :address')
                ->setParameter('address', '%' . $criteria['address'] . '%');
        }

        // if (!empty($criteria['specialty'])) {
        //     $qb->andWhere('u.specialty LIKE :specialty')
        //         ->setParameter('specialty', '%' . $criteria['specialty'] . '%');
        // }

        if (!empty($criteria['specialty'])) {
            $qb->andWhere('u.specialty IN (:specialty)')
                ->setParameter('specialty', $criteria['specialty']);
        }

        if (!empty($criteria['gender'])) {
            $qb->andWhere('u.gender IN (:gender)')
                ->setParameter('gender', $criteria['gender']);
        }

        return $qb->getQuery()->getResult();
    }
    public function findDoctorsByDate(\DateTime $date)
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DOCTOR%') // Chỉ lấy bác sĩ
    
            // Truy vấn vào ScheduleWork và lọc theo ngày
        ->leftJoin('u.scheduleWorks', 's')  // Thực hiện left join vào scheduleWorks
        ->andWhere('s.date = :date') // Lọc theo ngày của lịch làm việc
        ->setParameter('date', $date->format('Y-m-d')); // Chuyển đổi đối tượng DateTime sang định dạng Y-m-d
    
        return $qb->getQuery()->getResult();
    }
    
    // public function findDoctorsByDate(string $date)
    // {
    //     return $this->createQueryBuilder('d')
    //         ->innerJoin('d.scheduleWorks', 's') // Giả sử Doctor có quan hệ với ScheduleWork
    //         ->where('s.date = :date')
    //         ->setParameter('date', new \DateTime($date)) // Chuyển đổi sang DateTime
    //         ->getQuery()
    //         ->getResult();
    // }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
