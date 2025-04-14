<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;


class AppointmentController extends AbstractController
{
    private EntityManagerInterface $em;
    private SessionInterface $session;
    private AppointmentRepository $apRepo;

    public function __construct(EntityManagerInterface $em, RequestStack $session, AppointmentRepository $apRepo)
    {
        $this->em = $em;
        $this->session = $session->getSession();
        $this->apRepo = $apRepo;
    }
    #[Route('/appointment', name: 'app_appointment')]
    public function index(): Response
    {
        return $this->render('appointment/index.html.twig', [
            'controller_name' => 'AppointmentController',
        ]);
    }

    #[Route('/check-appointment', name: 'check_appointment', methods: ['GET'])]
    public function checkAppointment(Request $request): Response
    {
        $doctorId = $request->query->get('doctorId');
        $date = new \DateTime($request->query->get('date'));

        $timeSlot = $request->query->get('timeSlot');

        if (!$doctorId || !$date || !$timeSlot) {
            return new JsonResponse(['error' => true, 'message' => 'Thiếu dữ liệu!'], 400);
        }

        $doctor = $this->em->getRepository(User::class)->find($doctorId);
        if (!$doctor) {
            return new JsonResponse(['error' => true, 'message' => 'Bác sĩ không tồn tại!'], 404);
        }

        $dateString = $request->query->get('date');
        if (!$dateString) {
            return new JsonResponse(['error' => true, 'message' => 'Thiếu dữ liệu ngày!'], 400);
        }

        try {
            $date = new \DateTime($dateString);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => true, 'message' => 'Định dạng ngày không hợp lệ!'], 400);
        }


        $patient = $this->getUser();
        if (!$patient) {
            // Tạo URL để quay lại sau khi đăng nhập
            $loginUrl = $this->generateUrl('app_login', [
                '_target_path' => $this->generateUrl('check_appointment', [
                    'doctorId' => $doctorId,
                    'date' => $date,
                    'timeSlot' => $timeSlot
                ])
            ]);

            return new JsonResponse(['error' => false, 'redirect' => $loginUrl], 302);
        }

        // Đã tồn tại appointment in day => 403
        $existingAppointments = $this->apRepo->findByDoctorAndDate($doctor, $date, $patient);
        if (!empty($existingAppointments)) {
            return new JsonResponse(['error' => true, 'message' => 'Không được phép đặt nhiều buổi trong ngày!'], 403);
        }

        // Ai cho BS Test khám trời
        if ($patient->getRoles()[0] == 'ROLE_DOCTOR') {
            return new JsonResponse(['error' => true, 'message' => 'Tài khoản Bác sĩ không thể đặt lịch khám!'], 403);
        }

        // dump($request->query->all());
        // die();

        $formattedDate = $date->format('Y-m-d');
        // Trả về URL để JavaScript redirect
        $confirmUrl = $this->generateUrl('confirm_payment', [
            'doctorId' => $doctorId,
            'date' => $formattedDate,
            'timeSlot' => $timeSlot
        ]);

        return new JsonResponse(['error' => false, 'redirect' => $confirmUrl], 200);
    }


    #[Route('/confirm-payment', name: 'confirm_payment', methods: ['GET'])]
    public function confirmPayment(Request $request): Response
    {
        $doctorId = $request->query->get('doctorId');
        $doctor = $this->em->getRepository(User::class)->find($doctorId);
        if (!$doctor) {
            return new JsonResponse(['message' => 'Bác sĩ không tồn tại!'], 404);
        }
        $date = $request->query->get('date');

        // dump($request->query->all());
        // die();
        $timeSlot = $request->query->get('timeSlot');

        if (!$doctorId || !$date || !$timeSlot) {
            return new JsonResponse(['message' => 'Thiếu dữ liệu!'], 400);
        }

        $patient = $this->getUser();
        if (!$patient) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('appointment/confirm_payment.html.twig', [
            'doctor' => $doctor,
            'patient' => $patient,
            'date' => $date,
            'timeSlot' => $timeSlot
        ]);
    }

    #[Route('/process-appointment', name: 'process_appointment', methods: ['POST'])]
    public function processAppointment(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $doctorId = $request->request->get('doctorId');
        $doctor = $this->em->getRepository(User::class)->find($doctorId);
        if (!$doctor) {
            return new Response('Bác sĩ không tồn tại!', 404);
        }

        $date = new \DateTime($request->request->get('date'));
        $timeSlot = $request->request->get('timeSlot');

        $appointmentData = [
            'doctorId' => $doctorId,
            'specialtyId' => $doctor->getSpecialty()->getId(),
            'appointmentDate' => $date->format('d-m-Y'),
            'appointmentTime' => $timeSlot,
            'patientFullname' => $request->request->get('patientFullname'),
            'patientDateOfBirth' => $request->request->get('patientDateOfBirth'),
            'patientPhoneNumber' => $request->request->get('patientPhoneNumber'),
            'patientAddress' => $request->request->get('patientAddress'),
            'patientGender' => $request->request->get('patientGender'),
            'patientEmail' => $request->request->get('patientEmail'),
            'forWho' => $request->request->get('forWho'),
            'reason' => $request->request->get('reason'),
            'price' => $doctor->getConsultationFee()
        ];

        // Lưu thông tin vào session
        $this->session->set('appointment_data', $appointmentData);

        return $this->redirectToRoute('payment_momo');
    }


    // #[Route('/process-appointment', name: 'process_appointment', methods: ['POST'])]
    // public function processAppointment(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     $user = $this->getUser();

    //     if (!$user instanceof User) {
    //         return $this->redirectToRoute('app_login');
    //     }

    //     $doctorId = $request->request->get('doctorId');
    //     $date = new \DateTime($request->request->get('date'));
    //     $timeSlot = $request->request->get('timeSlot');

    //     $patientFullname = $request->request->get('patientFullname');
    //     $patientDateOfBirth = new \DateTime($request->request->get('patientDateOfBirth'));
    //     $patientPhoneNumber = $request->request->get('patientPhoneNumber');
    //     $patientAddress = $request->request->get('patientAddress');
    //     $patientGender = $request->request->get('patientGender');
    //     $patientEmail = $request->request->get('patientEmail');
    //     $forWho = $request->request->get('forWho');
    //     $reason = $request->request->get('reason');

    //     $doctor = $entityManager->getRepository(User::class)->find($doctorId);
    //     if (!$doctor) {
    //         return new Response('Bác sĩ không tồn tại!', 404);
    //     }

    //     $appointment = new Appointment();
    //     $appointment->setPatient($user);
    //     $appointment->setDoctor($doctor);
    //     $appointment->setPatientFullname($patientFullname);
    //     $appointment->setPatientDateOfBirth($patientDateOfBirth);
    //     $appointment->setPatientPhoneNumber($patientPhoneNumber);
    //     $appointment->setPatientAddress($patientAddress);
    //     $appointment->setPatientGender($patientGender);
    //     $appointment->setPatientEmail($patientEmail);
    //     $appointment->setForWho($forWho);
    //     $appointment->setReason($reason);
    //     $appointment->setAppointmentDate($date);
    //     $appointment->setAppointmentTime($timeSlot);
    //     $appointment->setPrice($doctor->getConsultationFee());
    //     $appointment->setStatus(AppointmentConstants::PENDING_STATUS);
    //     $appointment->setPaymentStatus(AppointmentConstants::UNPAID_STATUS);

    //     $entityManager->persist($appointment);
    //     $entityManager->flush();

    //     // Gửi email xác nhận
    //     $this->mailService->sendAppointmentConfirmation(
    //         $patientEmail,
    //         $user->getFullname(),
    //         $appointment->getAppointmentDate()->format('Y-m-d'),
    //         $doctor->getFullName()
    //     );

    //     return $this->redirectToRoute('appointment_success');
    // }


    #[Route('/appointment-success', name: 'appointment_success', methods: ['GET'])]
    public function appointmentSuccess(): Response
    {
        $appointmentSuccess = $this->session->get('appointment_success');

        return $this->render('appointment/success.html.twig', [
            'appointment' => $appointmentSuccess
        ]);
    }

    // ------------------------------------------------------------------ Test Mail ------------------------------------------------------------------


    // #[Route('/send-test-email', name: 'send_test_email')]
    // public function sendTestEmail(MailerInterface $mailer)
    // {
    //     $email = (new Email())
    //         ->from('ntakhoa.work@gmail.com')
    //         ->to('khoasinno@gmail.com')
    //         ->subject('Test Email')
    //         ->text('This is a test email from Symfony Mailer.');

    //     $mailer->send($email);
    //     return new JsonResponse(['message' => 'Email sent successfully!']);
    // }

    // ------------------------------------------------------------------ Case substitutions business logic ------------------------------------------------------------------
    // flow Book trực tiếp ko confirm 
    // #[Route('/book-appointment', name: 'book_appointment', methods: ['POST'])]
    // public function bookAppointment(Request $request, EntityManagerInterface $em): JsonResponse
    // {
    //     // Kiểm tra user đã đăng nhập chưa
    //     $user = $this->getUser();

    //     if (!$user || !in_array('ROLE_PATIENT', $user->getRoles())) {
    //         return new JsonResponse(['success' => false, 'message' => 'Bạn cần đăng nhập với vai trò bệnh nhân'], 403);
    //     }

    //     // Lấy dữ liệu từ request
    //     $data = json_decode($request->getContent(), true);
    //     if (!isset($data['doctorId'], $data['date'], $data['timeSlot'])) {
    //         return new JsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ'], 400);
    //     }

    //     $doctor = $em->getRepository(User::class)->find($data['doctorId']);
    //     if (!$doctor || !in_array('ROLE_DOCTOR', $doctor->getRoles())) {
    //         return new JsonResponse(['success' => false, 'message' => 'Không tìm thấy bác sĩ'], 404);
    //     }

    //     // Tạo đối tượng Appointment
    //     $appointment = new Appointment();
    //     $appointment->setPatient($user);
    //     $appointment->setDoctor($doctor);
    //     $appointment->setAppointmentDate(new \DateTime($data['date']));
    //     $appointment->setAppointmentTime($data['timeSlot']);
    //     $appointment->setStatus('Pending');

    //     // Lưu vào DB
    //     $em->persist($appointment);
    //     $em->flush();

    //     return new JsonResponse(['success' => true, 'message' => 'Đặt lịch thành công']);
    // }
}
