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

    /**
     * Tìm bác sĩ theo các tiêu chí (name, address, specialty)
     */
    public function findDoctorsByCriteria(array $criteria)
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DOCTOR%'); // Tìm kiếm chỉ những bác sĩ

        // Kiểm tra nếu 'fullname' tồn tại và không rỗng
        if (!empty($criteria['name'])) {
            $qb->andWhere('u.fullname LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        // Kiểm tra nếu 'address' tồn tại và không rỗng
        if (!empty($criteria['address'])) {
            $qb->andWhere('u.address LIKE :address')
                ->setParameter('address', '%' . $criteria['address'] . '%');
        }

        // Kiểm tra nếu 'specialty' tồn tại và không rỗng
        if (!empty($criteria['specialty'])) {
            $qb->andWhere('u.specialty LIKE :specialty')
                ->setParameter('specialty', '%' . $criteria['specialty'] . '%');
        }

        return $qb->getQuery()->getResult();
    }



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
