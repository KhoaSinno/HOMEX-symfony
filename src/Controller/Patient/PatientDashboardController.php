<?php

namespace App\Controller\Patient;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PatientDashboardController extends AbstractController
{
    #[Route('/patient/dashboard', name: 'app_patient_dashboard')]
    public function index(): Response
    {
        return $this->render('patient/dashboard/index.html.twig', [
            'controller_name' => 'PatientDashboardController',
        ]);
    }
}
