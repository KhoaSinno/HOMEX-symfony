<?php

namespace App\Repository;

use App\Entity\Momo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MomoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Momo::class);
    }

    public function storeMomoInfo(int $customerId, int $momoStatus, string $linkData): Momo
    {
        $entityManager = $this->getEntityManager();
        
        $momo = new Momo();
        $momo->setCustomerId($customerId);
        $momo->setMomoStatus($momoStatus);
        $momo->setLinkData($linkData);

        $entityManager->persist($momo);
        $entityManager->flush();

        return $momo;
    }
}
