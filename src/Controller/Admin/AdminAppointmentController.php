<?php

namespace App\Controller\Admin;

use App\Constants\AppointmentConstants;
use App\Entity\Appointment;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/appointment')]
final class AdminAppointmentController extends AbstractController
{
    #[Route(name: 'app_admin_appointment', methods: ['GET'])]
    public function index(AppointmentRepository $appointmentRepository): Response
    {
        return $this->render('admin/appointment/index.html.twig', [
            'appointments' => $appointmentRepository->findAll(),
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

    // #[Route('/{id}', name: 'app_admin_appointment_delete', methods: ['POST'])]
    // public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    // {
    //     if ($this->isCsrfTokenValid('delete'.$appointment->getId(), $request->getPayload()->getString('_token'))) {
    //         $entityManager->remove($appointment);
    //         $entityManager->flush();
    //     }

    //     return $this->redirectToRoute('app_admin_appointment', [], Response::HTTP_SEE_OTHER);
    // }
    #[Route('/{id}/delete', name: 'app_admin_appointment_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $appointment->getId(), $request->request->get('_token'))) {
            $appointment->setStatus(AppointmentConstants::CANCELLED_STATUS);
            $entityManager->persist($appointment);
            $entityManager->flush();
            $this->addFlash('success', 'Đã hủy lịch hẹn thành công!');
        } else {
            $this->addFlash('danger', 'Lỗi CSRF, vui lòng thử lại!');
        }

        return $this->redirectToRoute('app_admin_appointment');
    }
}
