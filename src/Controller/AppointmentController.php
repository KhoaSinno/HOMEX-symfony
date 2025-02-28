<?php

namespace App\Controller;

use App\Constants\AppointmentConstants;
use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\ScheduleWorkRepository;
use App\Repository\SpecialtyRepository;
use App\Service\MailService;
use App\Service\ScheduleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Email;


class AppointmentController extends AbstractController
{
    private EntityManagerInterface $em;
    private SpecialtyRepository $specialtyRepository;
    private ScheduleService $scheduleService;
    private ScheduleWorkRepository $scheduleRepo;
    private MailService $mailService;
    private SessionInterface $session;

    public function __construct(EntityManagerInterface $em, SpecialtyRepository $specialtyRepository, ScheduleService $scheduleService, ScheduleWorkRepository $scheduleRepo, MailService $mailService, RequestStack $session)
    {
        $this->em = $em;
        $this->specialtyRepository = $specialtyRepository;
        $this->scheduleService = $scheduleService;
        $this->scheduleRepo = $scheduleRepo;
        $this->mailService = $mailService;
        $this->session = $session->getSession();
    }
    #[Route('/appointment', name: 'app_appointment')]
    public function index(): Response
    {
        return $this->render('appointment/index.html.twig', [
            'controller_name' => 'AppointmentController',
        ]);
    }

    #[Route('/confirm-payment', name: 'confirm_payment', methods: ['GET'])]
    public function confirmPayment(Request $request): Response
    {
        $doctorId = $request->query->get('doctorId');
        $date = new \DateTime($request->query->get('date'));
        $timeSlot = $request->query->get('timeSlot');

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $doctor = $this->em->getRepository(User::class)->find($doctorId);
        if (!$doctor) {
            return new Response('Bác sĩ không tồn tại!', 404);
        }

        return $this->render('appointment/confirm_payment.html.twig', [
            'doctor' => $doctor,
            'patient' => $user,
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


    #[Route('/send-test-email', name: 'send_test_email')]
    public function sendTestEmail(MailerInterface $mailer)
    {
        $email = (new Email())
            ->from('ntakhoa.work@gmail.com')
            ->to('khoasinno@gmail.com')
            ->subject('Test Email')
            ->text('This is a test email from Symfony Mailer.');

        $mailer->send($email);
        return new JsonResponse(['message' => 'Email sent successfully!']);
    }

    // ------------------------------------------------------------------ Case substitutions business logic ------------------------------------------------------------------
    // flow Book trực tiếp ko confirm 
    #[Route('/book-appointment', name: 'book_appointment', methods: ['POST'])]
    public function bookAppointment(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Kiểm tra user đã đăng nhập chưa
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_PATIENT', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'Bạn cần đăng nhập với vai trò bệnh nhân'], 403);
        }

        // Lấy dữ liệu từ request
        $data = json_decode($request->getContent(), true);
        if (!isset($data['doctorId'], $data['date'], $data['timeSlot'])) {
            return new JsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ'], 400);
        }

        $doctor = $em->getRepository(User::class)->find($data['doctorId']);
        if (!$doctor || !in_array('ROLE_DOCTOR', $doctor->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'Không tìm thấy bác sĩ'], 404);
        }

        // Tạo đối tượng Appointment
        $appointment = new Appointment();
        $appointment->setPatient($user);
        $appointment->setDoctor($doctor);
        $appointment->setAppointmentDate(new \DateTime($data['date']));
        $appointment->setAppointmentTime($data['timeSlot']);
        $appointment->setStatus('Pending');

        // Lưu vào DB
        $em->persist($appointment);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Đặt lịch thành công']);
    }
}
