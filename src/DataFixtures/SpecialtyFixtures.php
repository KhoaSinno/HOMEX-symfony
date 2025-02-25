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
        $specialty_Urology->setImage('urology_logo.png');
        $specialty_Urology->setClinicNumber('C01');
        $manager->persist($specialty_Urology);
        
        // Neurology
        $specialty_Neurology = new Specialty();
        $specialty_Neurology->setName('Neurology');
        $specialty_Neurology->setImage('neurology_logo.png');
        $specialty_Neurology->setClinicNumber('C02');
        $manager->persist($specialty_Neurology);
        
        // Orthopedic
        $specialty_Orthopedic = new Specialty();
        $specialty_Orthopedic->setName('Orthopedic');
        $specialty_Orthopedic->setImage('orthopedic_logo.png');
        $specialty_Orthopedic->setClinicNumber('C03');
        $manager->persist($specialty_Orthopedic);
        
        // Cardiologist
        $specialty_Cardiologist = new Specialty();
        $specialty_Cardiologist->setName('Cardiologist');
        $specialty_Cardiologist->setImage('cardiologist_logo.png');
        $specialty_Cardiologist->setClinicNumber('C04');
        $manager->persist($specialty_Cardiologist);
        
        // Lưu reference để dùng cho DoctorFixture
        $this->addReference('specialty-cardiology', $specialty_Cardiologist);

        // Dentist
        $specialty_Dentist = new Specialty();
        $specialty_Dentist->setName('Dentist');
        $specialty_Dentist->setImage('dentist_logo.png');
        $specialty_Dentist->setClinicNumber('C05');
        $manager->persist($specialty_Dentist);

        $manager->flush();
    }
}
