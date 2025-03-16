<?php

namespace App\Repository;

use App\Entity\Momo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MomoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Momo::class);
    }

    public function storeMomoInfo(User $customer, int $momoStatus, string $linkData): Momo
    {
        $entityManager = $this->getEntityManager();
        
        $momo = new Momo();
        $momo->setCustomer($customer);
        $momo->setMomoStatus($momoStatus);
        $momo->setLinkData($linkData);
        $momo->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($momo);
        $entityManager->flush();

        return $momo;
    }
}
