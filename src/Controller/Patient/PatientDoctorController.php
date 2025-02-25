<?php

namespace App\Controller\Patient;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PatientDoctorController extends AbstractController
{
    #[Route('/patient/doctor', name: 'app_patient_doctor')]
    public function index(): Response
    {
        return $this->render('patient/doctor/profile.html.twig', [
            'controller_name' => 'PatientDoctorController',
        ]);
    }
}
