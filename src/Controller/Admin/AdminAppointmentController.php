<?php

namespace App\Controller\Admin;

use App\Constants\AppointmentConstants;
use App\Entity\Appointment;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/appointment')]
final class AdminAppointmentController extends AbstractController
{

    private MailService $mailService;
    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }
    #[Route(name: 'app_admin_appointment', methods: ['GET'])]
    public function index(AppointmentRepository $appointmentRepository): Response
    {
        return $this->render('admin/appointment/index.html.twig', [
            'appointments' => $appointmentRepository->findBy(['status' => AppointmentConstants::PENDING_STATUS]),
        ]);
    }

    #[Route('/admin/appointments/deleted', name: 'app_admin_appointment_listDel', methods: ['GET'])]
    public function listDel(AppointmentRepository $appointmentRepository): Response
    {
        return $this->render('admin/appointment/listDel.html.twig', [
            'appointments' => $appointmentRepository->findBy(['status' => AppointmentConstants::CANCELLED_STATUS]),
        ]);
    }

    #[Route('/admin/appointments/success', name: 'app_admin_appointment_listSuccess', methods: ['GET'])]
    public function listSuccess(AppointmentRepository $appointmentRepository): Response
    {
        return $this->render('admin/appointment/listSuccess.html.twig', [
            'appointments' => $appointmentRepository->findBy(['status' => AppointmentConstants::COMPLETED_STATUS]),
        ]);
    }

    #[Route('/new', name: 'app_admin_appointment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $appointment = new Appointment();
        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($appointment);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_appointment', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/appointment/new.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_appointment_show', methods: ['GET'])]
    public function show(Appointment $appointment): Response
    {
        return $this->render('admin/appointment/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_appointment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_appointment', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/appointment/edit.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_admin_appointment_approve', methods: ['POST', 'APPROVE'])]
    public function approve(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('approve' . $appointment->getId(), $request->request->get('_token'))) {
            $appointment->setStatus(AppointmentConstants::COMPLETED_STATUS);
            $appointment->setPaymentStatus(AppointmentConstants::PAID_STATUS);
            $entityManager->persist($appointment);
            $entityManager->flush();
            $this->addFlash('success', 'Duyệt lịch hẹn thành công!');
        } else {
            $this->addFlash('danger', 'Lỗi CSRF, vui lòng thử lại!');
        }

        return $this->redirectToRoute('app_admin_appointment');
    }

    #[Route('/{id}/delete', name: 'app_admin_appointment_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $appointment->getId(), $request->request->get('_token'))) {
            $appointment->setStatus(AppointmentConstants::CANCELLED_STATUS);

            // Gửi email Xin Lỗi hủy
            $this->mailService->sendAppointmentCancellation(
                $appointment->getPatientEmail(),
                $appointment->getPatient()->getFullname(),
                $appointment->getAppointmentDate()->format('d-m-Y'),
                $appointment->getDoctor()->getFullName()
            );

            $entityManager->persist($appointment);
            $entityManager->flush();
            $this->addFlash('success', 'Đã hủy lịch hẹn thành công!');
        } else {
            $this->addFlash('danger', 'Lỗi CSRF, vui lòng thử lại!');
        }

        return $this->redirectToRoute('app_admin_appointment');
    }
}
