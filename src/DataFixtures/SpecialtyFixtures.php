<?php

namespace App\DataFixtures;

use App\Entity\Specialty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SpecialtyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Urology
        $specialty_Urology = new Specialty();
        $specialty_Urology->setName('Urology');
        $specialty_Urology->setImage('59e5cd55ee264349ffd138d51d97916a.png');
        $specialty_Urology->setClinicNumber('C01');
        $manager->persist($specialty_Urology);
        
        // Neurology
        $specialty_Neurology = new Specialty();
        $specialty_Neurology->setName('Neurology');
        $specialty_Neurology->setImage('324a8555cee20111b449f4e78a255242.png');
        $specialty_Neurology->setClinicNumber('C02');
        $manager->persist($specialty_Neurology);
        
        // Orthopedic
        $specialty_Orthopedic = new Specialty();
        $specialty_Orthopedic->setName('Orthopedic');
        $specialty_Orthopedic->setImage('82cc106ccf9180a9c09fbc18ba7b0b47.png');
        $specialty_Orthopedic->setClinicNumber('C03');
        $manager->persist($specialty_Orthopedic);
        
        // Cardiologist
        $specialty_Cardiologist = new Specialty();
        $specialty_Cardiologist->setName('Cardiologist');
        $specialty_Cardiologist->setImage('bf0bc077e1034484b8c6a6f82c4a2bce.png');
        $specialty_Cardiologist->setClinicNumber('C04');
        $manager->persist($specialty_Cardiologist);
        
        // Dentist
        $specialty_Dentist = new Specialty();
        $specialty_Dentist->setName('Dentist');
        $specialty_Dentist->setImage('8f108637e4413471db74997f18d11532.png');
        $specialty_Dentist->setClinicNumber('C05');
        $manager->persist($specialty_Dentist);

        $manager->flush();
    }
}
