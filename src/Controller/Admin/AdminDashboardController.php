<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminDashboardController extends AbstractController
{
    private $em;
    private $appRepo;
    private $userRepo;
    public function __construct(EntityManagerInterface $_em, AppointmentRepository $_appRepo, UserRepository $_userRepo)
    {
        $this->em = $_em;
        $this->appRepo = $_appRepo;
        $this->userRepo = $_userRepo;
    }
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        $doctors = $this->userRepo->findByRole('ROLE_DOCTOR');
        $patients = $this->userRepo->findByRole('ROLE_PATIENT');
        $appointments = $this->appRepo->findAll();

        $totalAppointment = count($appointments);
        $totalDoctor = count($doctors);
        $totalPatient = count($patients);
        $totalRevenue = $this->appRepo->getTotalRevenue() ?? 0;


        return $this->render('admin/dashboard/index.html.twig', [
            'doctors' => $doctors,
            'patients' => $patients,
            'appointments' => $appointments,
            'totalAppointment' => $totalAppointment,
            'totalDoctor' => $totalDoctor,
            'totalPatient' => $totalPatient,
            'totalRevenue' => $totalRevenue,
        ]);
    }
}
