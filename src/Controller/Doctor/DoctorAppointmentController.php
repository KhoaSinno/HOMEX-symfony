<?php

namespace App\Controller\Doctor;

use App\Constants\AppointmentConstants;
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


    #[Route('/show/{id}', name: 'app_doctor_appointment_show')]
    public function showAppointment(Appointment $appointment): Response
    {
        // Kiểm tra nếu không tìm thấy cuộc hẹn
        if (!$appointment) {
            throw $this->createNotFoundException('Không tìm thấy cuộc hẹn.');
        }

        return $this->render('doctor/dashboard/show_appointment.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_doctor_appointment_delete', methods: ['POST'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $appointment->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF không hợp lệ!');
        }

        $dateInput = $appointment->getAppointmentDate()->format('Y-m-d'); // "2025-02-28"
        $timeSlot = $appointment->getAppointmentTime(); // "7:00-7:30"

        // Lấy giờ bắt đầu
        $startTime = explode('-', $timeSlot)[0];

        // Xác định múi giờ chung
        $timezone = new \DateTimeZone("Asia/Ho_Chi_Minh");

        // Tạo thời gian lịch hẹn theo đúng múi giờ
        try {
            $appointmentDateTime = new \DateTime("$dateInput $startTime", $timezone);
        } catch (\Exception $e) {
            return new Response('Dữ liệu ngày/giờ không hợp lệ', 400);
        }

        // Lấy thời gian hiện tại cùng múi giờ
        $now = new \DateTime("now", $timezone);

        // Tính khoảng cách thời gian
        $interval = $now->diff($appointmentDateTime);
        $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

        // Kiểm tra nếu còn dưới 120 phút
        if ($totalMinutes < 120 && $interval->invert == 1) {
            $this->addFlash('danger', 'Bạn chỉ có thể hủy lịch hẹn trước 2 giờ.');
            return $this->redirectToRoute('app_doctor_appointment');
        }

        // Debug
        // dump($now, $appointmentDateTime, $interval, $totalMinutes);
        // die();


        // Nếu hợp lệ, hủy lịch
        $appointment->setStatus(AppointmentConstants::CANCELLED_STATUS);
        $appointment->setReasonCancel($request->getPayload()->getString('reasonCancel'));

        $entityManager->flush();
        $this->addFlash('success', 'Lịch hẹn đã được hủy thành công.');

        return $this->redirectToRoute('app_doctor_appointment');
    }


    // #[Route('/{id}/delete', name: 'app_doctor_appointment_delete', methods: ['POST'])]
    // public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    // {
    //     if ($this->isCsrfTokenValid('delete' . $appointment->getId(), $request->getPayload()->getString('_token'))) {
    //         $appointment->setStatus(AppointmentConstants::CANCELLED_STATUS);
    //         $appointment->setReasonCancel($request->getPayload()->getString('reasonCancel'));
    //         dump($appointment);
    //         die();
    //         $entityManager->remove($appointment);
    //         $entityManager->flush();
    //     }

    //     return $this->redirectToRoute('app_doctor_appointment', [], Response::HTTP_SEE_OTHER);
    // }
}
