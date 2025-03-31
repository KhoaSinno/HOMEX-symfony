<?php

namespace App\Controller\Doctor;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/doctor/patient')]

class DoctorPatientController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    #[Route('/', name: 'app_doctor_patient')]
    public function index(UserRepository $userRepo): Response
    {
        // $patients = $userRepo->findByRole('ROLE_PATIENT');

        $doctor = $this->getUser();
        if (!$doctor instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $patients = $this->em->getRepository(User::class)->findPatientsByDoctor($doctor);


        return $this->render('doctor/patient/index.html.twig', [
            'patients' => $patients,
        ]);
    }

    #[Route('/{id}', name: 'app_doctor_patient_show')]
    public function show($id, UserRepository $userRepo, AppointmentRepository $appointmentRepo): Response
    {
        $patient = $userRepo->find($id);

        // Cuộc hẹn gần nhất
        $appointments = $appointmentRepo->findAppointByPatient($patient);


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
