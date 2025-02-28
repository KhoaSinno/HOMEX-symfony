<?php

namespace App\Controller\Doctor;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/doctor/patient')]

class DoctorPatientController extends AbstractController
{
    #[Route('/', name: 'app_doctor_patient')]
    public function index(UserRepository $userRepo): Response
    {
        $patients = $userRepo->findByRole('ROLE_PATIENT');

        return $this->render('doctor/patient/index.html.twig', [
            'patients' => $patients,
        ]);
    }

    #[Route('/{id}', name: 'app_doctor_patient_show')]
    public function show($id, UserRepository $userRepo, AppointmentRepository $appointmentRepo): Response
    {
        $patient = $userRepo->find($id);

        // Cuộc hẹn gần nhất
        $appointments = $appointmentRepo->findByPatient($patient);

      
        // Hóa đơn thanh toán: Status = confirmed, PaymentStatus = paid
        $invoices = $appointmentRepo->findInvoices($patient);


        return $this->render('doctor/patient/profile.html.twig', [
            'patient' => $patient,
            'appointments' => $appointments,
            'invoices' => $invoices,
        ]);
    }

    #[Route('/appointment/show/{id}', name: 'app_doctor_patient_appointment_show')]
    public function showAppointment(Appointment $appointment): Response
    {
        // Kiểm tra nếu không tìm thấy cuộc hẹn
        if (!$appointment) {
            throw $this->createNotFoundException('Không tìm thấy cuộc hẹn.');
        }
    
        return $this->render('doctor/patient/show_appointment.html.twig', [
            'appointment' => $appointment,
        ]);
    }
}
