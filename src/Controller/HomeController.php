<?php

namespace App\Controller;

use App\Entity\Specialty;
use App\Repository\ScheduleWorkRepository;
use App\Repository\SpecialtyRepository;
use App\Repository\UserRepository;
use App\Service\ScheduleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private EntityManagerInterface $em;
    private SpecialtyRepository $specialtyRepository;
    private ScheduleService $scheduleService;

    public function __construct(EntityManagerInterface $em, SpecialtyRepository $specialtyRepository, ScheduleService $scheduleService)
    {
        $this->em = $em;
        $this->specialtyRepository = $specialtyRepository;
        $this->scheduleService = $scheduleService;
    }


    #[Route('/', name: 'app_home')]
    public function index(UserRepository $userRepository): Response
    {
        $specialties = $this->specialtyRepository->findAll();
        $doctors = $userRepository->findByRole('ROLE_DOCTOR');

        return $this->render('home/index.html.twig', [
            'specialties' => $specialties,
            'doctors' => $doctors,
        ]);
    }

    #[Route('/search/doctor', name: 'app_search_doctor')]
    public function searchDoctor(UserRepository $userRepository, Request $request): Response
    {
        // Lấy các tham số tìm kiếm từ yêu cầu
        $name = $request->query->get('fullname');
        $address = $request->query->get('address');
        $specialty = $request->query->get('specialty');

        $criteria = [];
        if ($name) {
            $criteria['name'] = $name;
        }
        if ($address) {
            $criteria['address'] = $address;
        }
        if ($specialty) {
            $criteria['specialty'] = $specialty;
        }

        // Tìm bác sĩ theo các tiêu chí
        $doctors = $userRepository->findDoctorsByCriteria($criteria);

        // Render kết quả tìm kiếm
        return $this->render('home/search_doctor.html.twig', [
            'doctors' => $doctors,
            'count' => count($doctors), // Đếm số lượng bác sĩ tìm được
            'search_address' => $address, // Lưu địa chỉ tìm kiếm
            'search_specialty' => $specialty, // Lưu chuyên khoa tìm kiếm
        ]);
    }

    #[Route('/home/doctor_profile', name: 'app_doctor_profile')]
    public function doctorProfile(
        Request $request, 
        UserRepository $userRepository, 
        ScheduleWorkRepository $scheduleWorkRepository
    ): Response {
        $id = $request->query->get('id');
        $doctor = $userRepository->find($id);
    
        if (!$doctor) {
            throw $this->createNotFoundException('Bác sĩ không tồn tại');
        }
    
        // Lấy tất cả các ngày có lịch khám của bác sĩ
        $availableDates = $scheduleWorkRepository->getAvailableDatesByDoctor($doctor);
    
        // Kiểm tra xem có ngày nào được chọn không
        $selectedDate = $request->query->get('date');
        if ($selectedDate) {
            $selectedDate = \DateTime::createFromFormat('Y-m-d', $selectedDate);
        } else {
            // Nếu không có ngày chọn, mặc định lấy ngày đầu tiên có trong lịch bác sĩ
            $selectedDate = !empty($availableDates) ? new \DateTime($availableDates[0]) : null;
        }
    
        // Lấy các khung giờ làm việc của bác sĩ
        $timeSlots = $selectedDate ? $scheduleWorkRepository->getTimeSlotsByDoctorAndDate($doctor, $selectedDate) : [];
    
        return $this->render('home/doctor_profile.html.twig', [
            'doctor' => $doctor,
            'availableDates' => $availableDates,
            'selectedDate' => $selectedDate,
            'timeSlots' => $timeSlots,
        ]);
    }
    







    // public function searchDoctor(UserRepository $userRepository, Request $request): Response
    // {
    //     $specialtyId = $request->query->get('specialty');
    //     $doctors = $userRepository->findBySpecialty($specialtyId);

    //     return $this->render('home/search_doctor.html.twig', [
    //         'doctors' => $doctors,
    //     ]);
    // }
}
