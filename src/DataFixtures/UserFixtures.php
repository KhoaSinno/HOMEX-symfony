<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Admin
        $admin = new User();
        $admin->setEmail('sinoo@gmail.com');
        $admin->setFullname('Sinoo');
        $admin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($admin, '123456');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // Doctor
        $doctor = new User();
        $doctor->setEmail('doe@gmail.com');
        $doctor->setFullname('Dr. John Doe');
        $doctor->setRoles(['ROLE_DOCTOR']);
        $hashedPassword = $this->passwordHasher->hashPassword($doctor, '123456');
        $doctor->setPassword($hashedPassword);
        $manager->persist($doctor);

        // Patient
        $patient = new User();
        $patient->setEmail('mary@gmail.com');
        $patient->setFullname('Mary');
        $patient->setRoles(['ROLE_PATIENT']);
        $hashedPassword = $this->passwordHasher->hashPassword($patient, '123456');
        $patient->setPassword($hashedPassword);
        $manager->persist($patient);

        $manager->flush();
    }
}
