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
            ->andWhere('u.isDel = :isDel')
            ->setParameter('role', '%"' . $role . '"%') // Tìm role trong mảng JSON
            ->setParameter('isDel', 0)
            ->getQuery()
            ->getResult();
    }

    public function findDoctorsByCriteria(array $criteria)
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.isDel = :isDel')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DOCTOR%') // Tìm kiếm chỉ những bác sĩ
            ->setParameter('isDel', 0);


        if (!empty($criteria['name'])) {
            $qb->andWhere('u.fullname LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['address'])) {
            $qb->andWhere('u.address LIKE :address')
                ->setParameter('address', '%' . $criteria['address'] . '%');
        }

        if (!empty($criteria['specialty'])) {
            if (is_array($criteria['specialty'])) {
                // Nếu specialty là mảng, dùng IN => tìm kiếm với nhiều chuyên khoa ấy mà, mà Business không có nên để thừa cũng chả sao
                $qb->join('u.specialty', 's')  // Join bảng specialty với alias 's'
                    ->andWhere('s.name IN (:specialty)')
                    ->setParameter('specialty', (array) $criteria['specialty']);  // Đảm bảo là mảng

            } else {
                // Nếu specialty không phải mảng, dùng LIKE
                $qb->innerJoin('u.specialty', 's') // Thực hiện join với bảng specialty
                    ->andWhere('s.name LIKE :specialty') // So sánh tên của specialty
                    ->setParameter('specialty', '%' . $criteria['specialty'] . '%');
            }
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

    public function findUserByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"' . $role . '"%')
            ->orderBy('u.fullname', 'ASC') // Sắp xếp theo tên
            ->getQuery()
            ->getResult();
    }
}
