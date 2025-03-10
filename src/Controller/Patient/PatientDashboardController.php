<?php

namespace App\Controller\Patient;

use App\Constants\AppointmentConstants;
use App\Entity\Appointment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class PatientDashboardController extends AbstractController
{
    private AppointmentRepository $appointmentRepo;
    private EntityManagerInterface $em;
    public function __construct(private AppointmentRepository $appointmentRepository, EntityManagerInterface $em)
    {
        $this->appointmentRepo = $appointmentRepository;
        $this->em = $em;
    }
    #[Route('/patient/dashboard', name: 'app_patient_dashboard')]

    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('User must be an instance of App\Entity\User');
        }

        $pendingAppointments = $this->appointmentRepo->findBy([
            'patient' => $user,
            'status' => AppointmentConstants::PENDING_STATUS,
        ]);

        $successAppointments = $this->appointmentRepo->findBy([
            'patient' => $user,
            'status' => AppointmentConstants::COMPLETED_STATUS,
        ]);


        return $this->render('patient/dashboard/index.html.twig', [
            'pendingAppointments' => $pendingAppointments,
            'successAppointments' => $successAppointments,
        ]);
    }

    #[Route('/appointment/show/{id}', name: 'app_appointment_show')]
    public function showAppointment(Appointment $appointment): Response
    {
        // Kiểm tra nếu không tìm thấy cuộc hẹn
        if (!$appointment) {
            throw $this->createNotFoundException('Không tìm thấy cuộc hẹn.');
        }

        return $this->render('patient/dashboard/show_appointment.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    #[Route('/appointment/edit/{id}', name: 'app_patient_appointment_edit')]
    public function editAppointment(Request $request, Appointment $appointment): Response
    {
        if (!$appointment) {
            throw $this->createNotFoundException('Không tìm thấy cuộc hẹn.');
        }

        if ($appointment->getStatus() === AppointmentConstants::COMPLETED_STATUS) {
            throw $this->createNotFoundException('Cuộc hẹn đã hoàn thành không thể chỉnh sửa.');
        }

        if($request->isMethod('POST')) {
            $appointment->setReason($request->request->get('reason'));
            $this->em->persist($appointment);
            $this->em->flush();

            // dump($appointment);
            // die();
            $this->addFlash('success', 'Cập nhật cuộc hẹn thành công.');

            return $this->redirectToRoute('app_patient_dashboard');
        }


        return $this->render('patient/dashboard/edit_appointment.html.twig', [
            'appointment' => $appointment,
        ]);
    }
    // #[Route('/appointment/cancel/{id}', name: 'app_appointment_cancel')]
    // public function cancelAppointment(): Response
    // {

    //     // Xử lý hủy cuộc hẹn
    // }


}
