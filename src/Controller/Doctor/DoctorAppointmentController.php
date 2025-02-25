<?php

namespace App\Controller\Doctor;

use App\Entity\Appointment;
use App\Form\DoctorAppointmentType;
use App\Repository\AppointmentRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use PhpZip\ZipFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/doctor/appointment')]
final class DoctorAppointmentController extends AbstractController
{

    private MailService $mailService;
    public function __construct(private MailService $_mailService)
    {
        $this->mailService = $_mailService;
    }
    #[Route(name: 'app_doctor_appointment', methods: ['GET'])]
    public function index(AppointmentRepository $appointmentRepository): Response
    {
        return $this->render('doctor/appointment/index.html.twig', [
            'appointments' => $appointmentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_doctor_appointment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $appointment = new Appointment();
        $form = $this->createForm(DoctorAppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($appointment);
            $entityManager->flush();

            return $this->redirectToRoute('app_doctor_appointment', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('doctor/appointment/new.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_doctor_appointment_show', methods: ['GET'])]
    public function show(Appointment $appointment): Response
    {
        return $this->render('doctor/appointment/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    // Doctor confirm appointment
    #[Route('/{id}/edit', name: 'app_doctor_appointment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DoctorAppointmentType::class, $appointment);
        $form->handleRequest($request);

        $patient = $appointment->getPatient();
        $doctor = $appointment->getDoctor();

        if (!$doctor) {
            throw new \LogicException('Cuộc hẹn này không có bác sĩ nào.');
        }

        $patientEmail = $patient ? $patient->getEmail() : null;
        $patientDate = $appointment->getAppointmentDate() ? $appointment->getAppointmentDate()->format('dmY') : '';
        // $securePassword = hash_hmac('sha256', $patientDate, 'secret_key');

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form['resultFile']->getData();

            if ($file && $file->isValid()) {
                $zipFileName = 'HOMEX_Ket_qua_' . $patient->getFullname() . '.zip';
                $zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;

                // **Tạo file ZIP và đặt mật khẩu**
                $zip = new ZipFile();
                $zip->addFile($file->getPathname(), $file->getClientOriginalName())
                    ->setPassword($patientDate)
                    ->saveAsFile($zipFilePath)
                    ->close();

                try {
                    $this->mailService->sendAppointmentResult(
                        $patientEmail,
                        $doctor->getFullname(),
                        $appointment->getAppointmentDate()->format('d-m-Y'),
                        $doctor->getFullName(),
                        $zipFilePath,
                        $zipFileName,
                        $patientDate
                    );
                } finally {
                    if (file_exists($zipFilePath)) {
                        unlink($zipFilePath);
                    }
                }

                return $this->redirectToRoute('app_doctor_dashboard');
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_doctor_appointment', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('doctor/appointment/edit.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    // public function edit(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    // {
    //     $form = $this->createForm(DoctorAppointmentType::class, $appointment);
    //     $form->handleRequest($request);
    //     $patientDate = $appointment->getAppointmentDate() ? $appointment->getAppointmentDate()->format('dmY') : ''; // Này phải trả về định dạng: 14072004

    //     $patientEmail = $appointment->getPatient()->getEmail();

    //     $doctor = $appointment->getDoctor();

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $file = $form['resultFile']->getData();

    //         if ($file) {
    //             $zipFileName = 'result_' . time() . '.zip';
    //             $zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;

    //             // **Tạo file ZIP và đặt mật khẩu**
    //             $zip = new ZipFile();
    //             $zip->addFile($file->getPathname(), $file->getClientOriginalName()) // Thêm file vào ZIP
    //                 ->setPassword($patientDate) // Đặt mật khẩu
    //                 ->saveAsFile($zipFilePath) // Lưu file ZIP
    //                 ->close();

    //             // Gửi email xác nhận
    //             $this->mailService->sendAppointmentResult(
    //                 $patientEmail,
    //                 $doctor->getFullname(),
    //                 $appointment->getAppointmentDate()->format('d-m-Y'),
    //                 $doctor->getFullName(),
    //                 $zipFilePath,
    //                 $zipFileName,
    //                 $patientDate
    //             );

    //             // **Xóa file sau khi gửi để tránh lưu trữ**
    //             unlink($zipFilePath);

    //             return $this->redirectToRoute('app_doctor_dashboard');
    //         }

    //         $entityManager->flush();

    //         return $this->redirectToRoute('app_doctor_appointment', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('doctor/appointment/edit.html.twig', [
    //         'appointment' => $appointment,
    //         'form' => $form,
    //     ]);
    // }

    #[Route('/{id}', name: 'app_doctor_appointment_delete', methods: ['POST'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $appointment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($appointment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_doctor_appointment', [], Response::HTTP_SEE_OTHER);
    }
}
