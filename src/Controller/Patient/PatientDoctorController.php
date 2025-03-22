<?php

namespace App\Controller\Patient;

use App\Entity\Appointment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PatientDoctorController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $_em)
    {
        $this->em = $_em;
    }
    #[Route('/patient/doctor', name: 'app_patient_doctor')]
    public function index(): Response
    {
        $patient = $this->getUser();
        if (!$patient instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $doctors = $this->em->getRepository(User::class)->findDoctorsByPatient($patient);

        // Kiểm tra chi tiết
        dump($patient->getId()); // ID của bệnh nhân
        dump($patient->getRoles()); // Vai trò của bệnh nhân
        dump($doctors); // Danh sách bác sĩ

        // Kiểm tra appointments của bệnh nhân
        $appointments = $this->em->getRepository(Appointment::class)->findBy(['patient' => $patient]);
        dump($appointments); // Xem tất cả lịch hẹn của bệnh nhân
        die();

        return $this->render('patient/doctor/index.html.twig', [
            'doctors' => $doctors, // Sửa typo
        ]);
    }
}
