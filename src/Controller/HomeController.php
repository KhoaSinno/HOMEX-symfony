<?php

namespace App\Controller;

use App\Repository\AppointmentRepository;
use App\Repository\ScheduleWorkRepository;
use App\Repository\SpecialtyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private EntityManagerInterface $em;
    private SpecialtyRepository $specialtyRepository;

    public function __construct(EntityManagerInterface $em, SpecialtyRepository $specialtyRepository)
    {
        $this->em = $em;
        $this->specialtyRepository = $specialtyRepository;
    }


    #[Route('/', name: 'app_home')]
    public function index(UserRepository $userRepository): Response
    {
        $specialties = $this->specialtyRepository->findAll();
        $doctors = $userRepository->findByRole('ROLE_DOCTOR');

        // Tính trung bình đánh giá cho từng bác sĩ
        foreach ($doctors as $doctor) {
            $reviews = $doctor->getReviews();
            if (count($reviews) > 0) {
                $totalRating = array_reduce($reviews->toArray(), fn($sum, $review) => $sum + $review->getRating(), 0);
                $doctor->setAverageRating($totalRating / count($reviews));
            } else {
                $doctor->setAverageRating(null);
            }
        }

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
        // $specialty = $request->query->get('specialty');
        $specialty = $request->query->get('specialty') ??  $request->query->all('select_specialist'); // Lấy danh sách chuyên khoa (mảng)
        $gender = $request->query->all('gender_type'); // Lấy danh sách giới tính (mảng)

        $criteria = [];
        if ($name) {
            $criteria['name'] = $name;
        }
        if ($address) {
            $criteria['address'] = $address;
        }
        if (!empty($specialty)) {
            $criteria['specialty'] = $specialty; // Lọc danh sách chuyên khoa
        }


        if (!empty($gender)) {
            $criteria['gender'] = $gender; // Lọc theo giới tính
        }

        // Tìm bác sĩ theo các tiêu chí
        $doctors = $userRepository->findDoctorsByCriteria($criteria);

        // Lấy giá trị ngày từ request
        $date = $request->query->get('date');

        if ($date) {
            // Chuyển đổi giá trị ngày thành đối tượng DateTime
            $dateObj = \DateTime::createFromFormat('d/m/Y', $date);
            if ($dateObj) {
                // Nếu chuyển đổi thành công, tìm bác sĩ theo ngày
                $doctors = $userRepository->findDoctorsByDate($dateObj);
            } else {
                // Nếu không thể chuyển đổi ngày, trả về thông báo lỗi hoặc không tìm bác sĩ
                $doctors = [];
            }
        } else {
            // Nếu không có ngày, tìm bác sĩ theo các tiêu chí đã cho
            $doctors = $userRepository->findDoctorsByCriteria($criteria);
        }

        // Tính trung bình đánh giá cho từng bác sĩ
        foreach ($doctors as $doctor) {
            $reviews = $doctor->getReviews();
            if (count($reviews) > 0) {
                $totalRating = array_reduce($reviews->toArray(), fn($sum, $review) => $sum + $review->getRating(), 0);
                $doctor->setAverageRating($totalRating / count($reviews));
            } else {
                $doctor->setAverageRating(null);
            }
        }

        // Render kết quả tìm kiếm
        return $this->render('home/search_doctor.html.twig', [
            'doctors' => $doctors,
            'count' => count($doctors), // Đếm số lượng bác sĩ tìm được
            'search_address' => $address, // Lưu địa chỉ tìm kiếm
            'search_specialty' => $specialty, // Lưu chuyên khoa tìm kiếm
            'search_gender' => $gender, // Lưu giới tính tìm kiếm
            'selected_date' => $date,
        ]);
    }


    #[Route('/home/doctor_profile', name: 'app_doctor_profile')]
    public function doctorProfile(
        Request $request,
        UserRepository $userRepository,
        ScheduleWorkRepository $scheduleWorkRepository,
        AppointmentRepository $appointmentRepository
    ): Response {
        $id = $request->query->get('id');
        $doctor = $userRepository->find($id);

        if (!$doctor) {
            throw $this->createNotFoundException('Bác sĩ không tồn tại');
        }

        // Lấy danh sách ngày làm việc của bác sĩ (chỉ lấy từ hiện tại trở đi)
        $availableDates = $scheduleWorkRepository->getAvailableDatesByDoctor($doctor);
        $timezone = new \DateTimeZone("Asia/Ho_Chi_Minh");
        $today = new \DateTime("now", $timezone);
        $today->setTime(0, 0, 0);
        $availableDates = array_values(array_filter($availableDates, function ($date) use ($today) {
            return new \DateTime($date) >= $today;
        }));

        // Xử lý ngày được chọn
        $selectedDate = $request->query->get('date');
        if ($selectedDate) {
            $selectedDate = \DateTime::createFromFormat('Y-m-d', $selectedDate);
        } else {
            $selectedDate = !empty($availableDates) ? new \DateTime($availableDates[0]) : null;
        }

        // Lấy danh sách khung giờ làm việc
        $timeSlots = $selectedDate ? $scheduleWorkRepository->getTimeSlotsByDoctorAndDate($doctor, $selectedDate) : [];

        // Lấy số lượng bệnh nhân đã đặt theo từng khung giờ
        // $bookedSlots = $selectedDate ? $appointmentRepository->getBookedPatientsByDoctorAndDate($doctor, $selectedDate) : [];

        // Lấy trạng thái từng khung giờ đã được đặt
        $slotStatuses = $selectedDate ? $appointmentRepository->getSlotStatusesByDoctorAndDate($doctor, $selectedDate) : [];

        /**
         * Kết quả giả định từ DB:
         * [
         *   "7:00-7:30" => "pending",   // Chờ khám
         *   "8:00-8:30" => "active",    // Đang khám
         *   "9:00-9:30" => "completed", // Đã khám xong
         *   "10:00-10:30" => "cancelled" // Hủy khám
         * ]
         */

        // Tính trung bình đánh giá
        $reviews = $doctor->getReviews();
        $averageRating = 0;

        if ($reviews && count($reviews) > 0) {
            $totalRating = array_reduce($reviews->toArray(), function ($sum, $review) {
                $rating = $review->getRating();
                return $sum + ($rating !== null ? $rating : 0); // Bỏ qua các giá trị null
            }, 0);

            $averageRating = $totalRating / count($reviews);
        }

        return $this->render('home/doctor_profile.html.twig', [
            'doctor' => $doctor,
            'availableDates' => $availableDates,
            'selectedDate' => $selectedDate,
            'timeSlots' => $timeSlots,
            'slotStatuses' => $slotStatuses,
            'averageRating' => $averageRating,
        ]);
    }

    // public function doctorProfile(
    //     Request $request,
    //     UserRepository $userRepository,
    //     ScheduleWorkRepository $scheduleWorkRepository,
    //     AppointmentRepository $appointmentRepository
    // ): Response {
    //     $id = $request->query->get('id');
    //     $doctor = $userRepository->find($id);

    //     if (!$doctor) {
    //         throw $this->createNotFoundException('Bác sĩ không tồn tại');
    //     }

    //     // Lấy danh sách ngày làm việc của bác sĩ (chỉ lấy từ hiện tại trở đi)
    //     $availableDates = $scheduleWorkRepository->getAvailableDatesByDoctor($doctor);
    //     $timezone = new \DateTimeZone("Asia/Ho_Chi_Minh");
    //     $today = new \DateTime("now", $timezone);
    //     $today->setTime(0, 0, 0);
    //     $availableDates = array_values(array_filter($availableDates, function ($date) use ($today) {
    //         return new \DateTime($date) >= $today;
    //     }));

    //     // Xử lý ngày được chọn
    //     $selectedDate = $request->query->get('date');
    //     if ($selectedDate) {
    //         $selectedDate = \DateTime::createFromFormat('Y-m-d', $selectedDate);
    //     } else {
    //         $selectedDate = !empty($availableDates) ? new \DateTime($availableDates[0]) : null;
    //     }

    //     // Lấy danh sách khung giờ làm việc
    //     $timeSlots = $selectedDate ? $scheduleWorkRepository->getTimeSlotsByDoctorAndDate($doctor, $selectedDate) : [];

    //     // Lấy số lượng bệnh nhân đã đặt theo từng khung giờ
    //     $bookedSlots = $selectedDate ? $appointmentRepository->getBookedPatientsByDoctorAndDate($doctor, $selectedDate) : [];

    //     /**
    //      * BookedSlots: return 
    //      * [
    //      * "7:00-7:30" => 2,
    //      * "8:00-8:30" => 1
    //      * ]
    //      * 
    //      */
    //     // Lấy maxPatient từ bảng ScheduleWork
    //     $maxPatient = $scheduleWorkRepository->getMaxPatientByDoctorAndDate($doctor, $selectedDate);

    //     // Xác định giờ nào cần disabled
    //     $disabledSlots = [];
    //     foreach ($timeSlots as $slot) {
    //         $bookedCount = $bookedSlots[$slot] ?? 0;
    //         $disabledSlots[$slot] = $bookedCount >= $maxPatient;
    //     }

    //     // [
    //     //     "7:00-7:30" => true,   // Disable vì đã có 2 người đặt
    //     //     "8:00-8:30" => false   // Vẫn đặt được vì mới có 1 người đặt
    //     // ]

    //     return $this->render('home/doctor_profile.html.twig', [
    //         'doctor' => $doctor,
    //         'availableDates' => $availableDates,
    //         'selectedDate' => $selectedDate,
    //         'timeSlots' => $timeSlots,
    //         'disabledSlots' => $disabledSlots,
    //     ]);
    // }


}
