<?php

namespace App\Controller\Doctor;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/doctor')]
class DoctorDashboardController extends AbstractController
{
    private $appointmentRepository;
    public function __construct(AppointmentRepository $appointmentRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
    }

    #[Route('/dashboard', name: 'app_doctor_dashboard')]
    public function index(): Response
    {
        $doctor = $this->getUser();
        if (!$doctor instanceof User) {
            throw new \LogicException('User must be an instance of App\Entity\User');
        }
     
        $appointments = $this->appointmentRepository->findByDoctor($doctor);
        
        $appointmentsToday = array_filter($appointments, function ($appointment) {
            return $appointment->getAppointmentDate()->format('Y-m-d') === (new \DateTime())->format('Y-m-d');
        });

        $appointmentsUpcoming = array_filter($appointments, function ($appointment) {
            return $appointment->getAppointmentDate() > new \DateTime();
        });

        $totalAppointments = count($appointments);
        $totalAppointmentsToday = count($appointmentsToday);

        $totalPatients = count(array_unique(array_map(function ($appointment) {
            return $appointment->getPatient()->getId();
        }, $appointments)));

        $totalPatientsToday = count(array_unique(array_map(function ($appointment) {
            return $appointment->getPatient()->getId();
        }, $appointmentsToday)));

        $date = new \DateTime();
        return $this->render('doctor/dashboard/index.html.twig', [
            'controller_name' => 'DoctorDashboardController',
            'appointments' => $appointments,
            'appointmentsToday' => $appointmentsToday,
            'appointmentsUpcoming' => $appointmentsUpcoming,
            'totalAppointments' => $totalAppointments,
            'totalPatients' => $totalPatients,
            'totalPatientsToday' => $totalPatientsToday,
            'totalAppointmentsToday' => $totalAppointmentsToday,
            'date' => $date,
        ]);
    }

    

   
}
