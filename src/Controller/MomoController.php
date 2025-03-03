<?php

namespace App\Controller;

use App\Constants\AppointmentConstants;
use App\Entity\Appointment;
use App\Entity\User;
use App\Service\MomoService;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MomoController extends AbstractController
{
    private MomoService $momoService;
    private SessionInterface $session;
    private EntityManagerInterface $em;
    private MailService $mailService;

    public function __construct(MomoService $momoService, RequestStack $session, EntityManagerInterface $em, MailService $mailService)
    {
        $this->momoService = $momoService;
        $this->session = $session->getSession();
        $this->em = $em;
        $this->mailService = $mailService;
    }

    #[Route('/payment/momo-ipn', name: 'momo_ipn', methods: ['POST'])]
    public function momoIpn(Request $request): JsonResponse
    {
        // Lấy dữ liệu từ MoMo
        $data = json_decode($request->getContent(), true);

        return new JsonResponse(['message' => 'IPN received'], 200);
    }

    #[Route('/payment/momo', name: 'payment_momo', methods: ['POST', 'GET'])]
    public function confirmMomo(Request $request): RedirectResponse
    {
        // Lấy dữ liệu từ session
        $appointmentData = $this->session->get('appointment_data');
        // dump($appointmentData);
        // die();

        if (!$appointmentData) {
            return $this->redirectToRoute('app_patient_dashboard', ['error' => 'Dữ liệu cuộc hẹn không hợp lệ']);
        }

        // $amount = (float) $request->get('amount', 0); // Ép kiểu về float và đặt giá trị mặc định
        $amount = (int) $appointmentData['price'];

        $orderId = time();
        $returnUrl = $this->generateUrl('momo_post', [], 0);
        $notifyUrl = $this->generateUrl('momo_ipn', [], 0);

        // Gọi service MoMo
        $response = $this->momoService->createPayment($amount, $orderId, $returnUrl, $notifyUrl);

        // dump($response['payUrl']);
        // dump($request);
        // dump($response);
        // die();

        $this->session->set('response_momo', $response);

        // return $this->redirect($response['payUrl']);


        return new RedirectResponse($response['payUrl']);
    }


    #[Route('/payment/momo/post', name: 'momo_post', methods: ['GET'])]
    public function momoPost(Request $request): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Kiểm tra kết quả thanh toán
        $resultCode = $request->query->get('resultCode');
        if ($resultCode != 0) {
            return $this->redirectToRoute('app_patient_dashboard', ['error' => 'Thanh toán thất bại']);
        }

        // Lấy dữ liệu từ session
        $appointmentData = $this->session->get('appointment_data');
        if (!$appointmentData) {
            return $this->redirectToRoute('app_patient_dashboard', ['error' => 'Không tìm thấy dữ liệu cuộc hẹn']);
        }

        // Laays dữ liệu từ MoMo
        $momoData = $this->session->get('response_momo');
        if ($momoData['resultCode'] !== 0) {
            return $this->redirectToRoute('app_patient_dashboard', ['error' => 'Thanh toán không thành công']);
        }

        // Tạo cuộc hẹn mới và lưu vào database
        $doctor = $this->em->getRepository(User::class)->find($appointmentData['doctorId']);
        if (!$doctor) {
            return $this->redirectToRoute('app_patient_dashboard', ['error' => 'Bác sĩ không tồn tại']);
        }

        try {

            $appointment = new Appointment();
            $appointment->setPatient($user);
            $appointment->setDoctor($doctor);
            $appointment->setPatientFullname($appointmentData['patientFullname']);
            $appointment->setPatientDateOfBirth(new \DateTime($appointmentData['patientDateOfBirth']));
            $appointment->setPatientPhoneNumber($appointmentData['patientPhoneNumber']);
            $appointment->setPatientAddress($appointmentData['patientAddress']);
            $appointment->setPatientGender($appointmentData['patientGender']);
            $appointment->setPatientEmail($appointmentData['patientEmail']);
            $appointment->setForWho($appointmentData['forWho']);
            $appointment->setReason($appointmentData['reason']);
            $appointment->setAppointmentDate(new \DateTime($appointmentData['appointmentDate']));
            $appointment->setAppointmentTime($appointmentData['appointmentTime']);
            $appointment->setPrice($doctor->getConsultationFee());
            $appointment->setStatus(AppointmentConstants::PENDING_STATUS); // Đã đặt lịch
            $appointment->setPaymentStatus(AppointmentConstants::PAID_STATUS); // Đã thanh toán

            $this->em->persist($appointment);
            $this->em->flush();

            // Gửi email xác nhận
            $this->mailService->sendAppointmentConfirmation(
                $appointmentData['patientEmail'],
                $user->getFullname(),
                $appointment->getAppointmentDate()->format('d-m-Y'),
                $doctor->getFullName()
            );

            // Xóa session sau khi hoàn thành
            $this->session->remove('appointment_data');

            $this->session->set('appointment_success', $appointment);
        } catch (\Throwable $th) {
            throw $th;
        }

        return $this->redirectToRoute('appointment_success');
    }

}
