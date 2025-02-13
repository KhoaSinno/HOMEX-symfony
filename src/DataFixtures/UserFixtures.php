<?php

namespace App\DataFixtures;

use App\Entity\Specialty;
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
        $admin->setImage('admin.jpg');
        $hashedPassword = $this->passwordHasher->hashPassword($admin, '123456');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // Doctor
        $doctor = new User();
        $doctor->setEmail('doe@gmail.com');
        $doctor->setFullname('Dr. John Doe');
        $doctor->setRoles(['ROLE_DOCTOR']);
        $doctor->setImage('doctor_doe_avt.jpg');
        $hashedPassword = $this->passwordHasher->hashPassword($doctor, '123456');
        $doctor->setPassword($hashedPassword);
        // Lấy specialty từ reference
        $specialty = $this->getReference('specialty-cardiology', Specialty::class);
        $doctor->setSpecialty($specialty);
        $manager->persist($doctor);
        
        // Patient
        $patient = new User();
        $patient->setEmail('mary@gmail.com');
        $patient->setFullname('Mary');
        $patient->setRoles(['ROLE_PATIENT']);
        $patient->setImage('patient_mary.jpg');
        $hashedPassword = $this->passwordHasher->hashPassword($patient, '123456');
        $patient->setPassword($hashedPassword);
        $manager->persist($patient);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            SpecialtyFixtures::class,
        ];
    }
}
