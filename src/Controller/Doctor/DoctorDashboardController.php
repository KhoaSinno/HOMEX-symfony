<?php

namespace App\Controller\Doctor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DoctorDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_doctor_dashboard')]
    public function index(): Response
    {
        return $this->render('doctor/dashboard/index.html.twig', [
            'controller_name' => 'DoctorDashboardController',
        ]);
    }
}
