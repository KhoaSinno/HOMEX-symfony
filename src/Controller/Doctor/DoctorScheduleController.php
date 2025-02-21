<?php

namespace App\Controller\Doctor;

use App\Entity\ScheduleWork;
use App\Form\DoctorScheduleWorkType;
use App\Repository\ScheduleWorkRepository;
use App\Repository\UserRepository;
use App\Service\ScheduleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/doctor/schedule')]
final class DoctorScheduleController extends AbstractController
{
    private $userRepository;
    private $em;
    private ScheduleService $scheduleService;
    function __construct(UserRepository $userRepository, EntityManagerInterface $em, ScheduleService $scheduleService)
    {
        $this->userRepository = $userRepository;
        $this->em = $em;
        $this->scheduleService = $scheduleService;
    }

    #[Route(name: 'app_doctor_schedule_index', methods: ['GET'])]
    public function index(ScheduleWorkRepository $scheduleWorkRepository, Request $request): Response
    {
        $doctor = $this->getUser();

        // Tạo form chọn ngày
        $form = $this->createFormBuilder(null, ['method' => 'GET'])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->getForm();

        // Xử lý request
        $form->handleRequest($request);

        // Lấy ngày từ form hoặc query string (Tránh lỗi input non-scalar)
        $formData = $request->query->all('form');
        $selectedDate = isset($formData['date']) ? new \DateTime($formData['date']) : new \DateTime();

        // Xác định ngày đầu tuần (Thứ Hai)
        $startDate = clone $selectedDate;
        if ($startDate->format('N') != 1) { // Nếu không phải Thứ Hai
            $startDate->modify('last Monday');
        }
        $endDate = clone $startDate;
        $endDate->modify('+6 days'); // Kết thúc vào Chủ Nhật

        // Lấy lịch làm việc của bác sĩ trong tuần đó
        $schedules = $scheduleWorkRepository->createQueryBuilder('s')
            ->where('s.doctor = :doctor')
            ->andWhere('s.date BETWEEN :startDate AND :endDate')
            ->setParameter('doctor', $doctor)
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('doctor/schedule_work/index.html.twig', [
            'schedules' => $schedules,
            'form' => $form->createView(),
            'selectedDate' => $selectedDate,
        ]);
    }







    // #[Route(name: 'app_doctor_schedule_index', methods: ['GET'])]
    // public function index(ScheduleWorkRepository $scheduleWorkRepository, Request $request): Response
    // {
    //     $daysOfWeek = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];

    //     $doctor = $this->getUser();

    //     // Lấy tháng từ request, mặc định là tháng hiện tại
    //     $month = $request->query->getInt('month', (int) date('m'));
    //     $week = $request->query->getInt('week', 1);

    //     // Xác định ngày đầu tiên và cuối cùng của tháng
    //     $firstDayOfMonth = new \DateTimeImmutable(date('Y') . '-' . $month . '-01');
    //     $lastDayOfMonth = $firstDayOfMonth->modify('last day of this month');

    //     // Xác định Thứ Hai đầu tiên của tháng
    //     $firstMonday = $firstDayOfMonth;
    //     if ($firstMonday->format('N') != 1) { // N: 1 = Monday, 7 = Sunday
    //         $firstMonday = $firstMonday->modify('next Monday');
    //     }

    //     // Tính tổng số ngày từ Thứ Hai đầu tiên đến hết tháng
    //     $daysFromFirstMonday = (int) $firstMonday->diff($lastDayOfMonth)->days + 1;

    //     // Số tuần thực tế (tính tròn lên)
    //     $totalWeeks = (int) ceil($daysFromFirstMonday / 7);

    //     // Đảm bảo giá trị tuần không vượt quá tổng số tuần thực tế
    //     $week = max(1, min($week, $totalWeeks));

    //     // Xác định khoảng thời gian của tuần được chọn
    //     $startDate = $firstMonday->modify('+' . (($week - 1) * 7) . ' days');
    //     $endDate = $startDate->modify('+6 days');

    //     // Giới hạn endDate không vượt quá ngày cuối tháng
    //     if ($endDate > $lastDayOfMonth) {
    //         $endDate = $lastDayOfMonth;
    //     }

    //     // Truy vấn lịch làm việc
    //     $schedules = $scheduleWorkRepository->createQueryBuilder('s')
    //         ->where('s.doctor = :doctor')
    //         ->andWhere('s.date BETWEEN :startDate AND :endDate')
    //         ->setParameter('doctor', $doctor)
    //         ->setParameter('startDate', $startDate->format('Y-m-d'))
    //         ->setParameter('endDate', $endDate->format('Y-m-d'))
    //         ->orderBy('s.date', 'ASC')
    //         ->getQuery()
    //         ->getResult();


    //     return $this->render('doctor/schedule_work/index.html.twig', [
    //         'schedules' => $schedules,
    //         'currentMonth' => $month,
    //         'currentWeek' => $week,
    //         'totalWeeks' => $totalWeeks, // Truyền số tuần thực tế sang view
    //     ]);
    // }


    #[Route('/add-slot', name: 'doctor_add_schedule_slot', methods: ['POST', 'GET'])]
    public function addSlot(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Tạo đối tượng ScheduleWork mới
        $scheduleWork = new ScheduleWork();


        // Tạo danh sách các khung giờ làm việc từ ScheduleService
        $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 30); // Ví dụ: Tạo từ 7h đến 17h, mỗi khung 30 phút

        // Tạo form từ ScheduleWork và truyền vào danh sách timeSlots
        $form = $this->createForm(DoctorScheduleWorkType::class, $scheduleWork, [
            'time_slots' => $timeSlots,
        ]);
        $form->handleRequest($request);

        // Kiểm tra nếu form được submit và hợp lệ
        if ($form->isSubmitted() && $form->isValid()) {
            $doctor = $this->getUser();
            $scheduleWork->setDoctor($doctor);

            $date = $form->get('date')->getData(); // Ngày làm việc
            $newSlots = $form->get('timeSlots')->getData(); // Các khung giờ mới

            if (!empty($newSlots)) {
                // Lấy danh sách khung giờ cũ (nếu có)
                $updatedSlots = $scheduleWork->getTimeSlots();

                // Đảm bảo không thêm trùng lặp
                foreach ($newSlots as $slot) {
                    if (!in_array($slot, $updatedSlots, true)) { // Chỉ thêm nếu chưa có
                        $updatedSlots[] = trim($slot);
                    }
                }

                // Sắp xếp lại danh sách timeslots theo giờ bắt đầu
                usort($updatedSlots, function ($a, $b) {
                    [$startA,] = explode('-', $a);
                    [$startB,] = explode('-', $b);
                    return strtotime($startA) - strtotime($startB);
                });

                // Cập nhật danh sách timeslots sau khi loại bỏ trùng lặp
                $scheduleWork->setTimeSlots(array_values($updatedSlots));

                $entityManager->persist($scheduleWork);
                $entityManager->flush();

                return $this->redirectToRoute('app_doctor_schedule_index');
            }
        }


        return $this->render('doctor/schedule_work/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    // public function addSlot(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     // Tạo đối tượng ScheduleWork mới
    //     $scheduleWork = new ScheduleWork();


    //     // Tạo danh sách các khung giờ làm việc từ ScheduleService
    //     $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 30); // Ví dụ: Tạo từ 7h đến 17h, mỗi khung 30 phút

    //     // Tạo form từ ScheduleWork và truyền vào danh sách timeSlots
    //     $form = $this->createForm(DoctorScheduleWorkType::class, $scheduleWork, [
    //         'time_slots' => $timeSlots,
    //     ]);
    //     $form->handleRequest($request);

    //     // Kiểm tra nếu form được submit và hợp lệ
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $doctor = $this->getUser();

    //         $scheduleWork->setDoctor($doctor);
    //         // Lấy dữ liệu từ form (Ngày và Giờ làm việc mới)
    //         $date = $form->get('date')->getData(); // Ngày làm việc
    //         $newSlots = $form->get('timeSlots')->getData(); // Các khung giờ mới

    //         // Kiểm tra nếu có giờ làm việc mới
    //         if (!empty($newSlots)) {
    //             // Lấy danh sách giờ làm việc cũ và thêm giờ mới
    //             $updatedSlots = $scheduleWork->getTimeSlots();
    //             foreach ($newSlots as $slot) {
    //                 $updatedSlots[] = trim($slot);
    //             }

    //             // Sắp xếp lại danh sách timeslots theo giờ bắt đầu
    //             usort($updatedSlots, function ($a, $b) {
    //                 [$startA,] = explode('-', $a);
    //                 [$startB,] = explode('-', $b);
    //                 return strtotime($startA) - strtotime($b);
    //             });

    //             // Cập nhật lại danh sách timeslots đã sắp xếp
    //             $scheduleWork->setTimeSlots(array_values($updatedSlots));

    //             // Lưu thay đổi vào cơ sở dữ liệu
    //             $entityManager->persist($scheduleWork);
    //             $entityManager->flush();

    //             return $this->redirectToRoute('app_doctor_schedule_index'); // Redirect đến trang danh sách lịch
    //         }
    //     }

    //     return $this->render('doctor/schedule_work/new.html.twig', [
    //         'form' => $form->createView(),
    //     ]);
    // }





    // #[Route('/new', name: 'app_doctor_schedule_new', methods: ['GET', 'POST'])]
    // public function new(Request $request, EntityManagerInterface $entityManager): Response
    // {

    //     $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 30);
    //     $scheduleWork = new ScheduleWork();



    //     $scheduleWork = new ScheduleWork();
    //     $form = $this->createForm(DoctorScheduleWorkType::class, $scheduleWork, [
    //         'time_slots' => $timeSlots,
    //     ]);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->persist($scheduleWork);
    //         $entityManager->flush();

    //         return $this->redirectToRoute('app_doctor_schedule_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('doctor/schedule_work/new.html.twig', [
    //         'schedule_work' => $scheduleWork,
    //         'form' => $form,
    //     ]);
    // }

    #[Route('/{id}', name: 'app_doctor_schedule_show', methods: ['GET'])]
    public function show(ScheduleWork $scheduleWork): Response
    {
        return $this->render('doctor/schedule_work/show.html.twig', [
            'schedule_work' => $scheduleWork,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_doctor_schedule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ScheduleWork $scheduleWork, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DoctorScheduleWorkType::class, $scheduleWork);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_doctor_schedule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('doctor/schedule_work/edit.html.twig', [
            'schedule_work' => $scheduleWork,
            'form' => $form,
        ]);
    }

    #[Route('/delete-slot/{id}', name: 'doctor_delete_schedule_slot', methods: ['POST'])]
    public function deleteSlot(ScheduleWork $scheduleWork, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $slotToDelete = $data['slot'] ?? null;

        if (!$slotToDelete) {
            return new JsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ!'], 400);
        }

        // Lọc bỏ slot cần xóa (đảm bảo trim khoảng trắng)
        $updatedSlots = array_filter(
            $scheduleWork->getTimeSlots(),
            fn($slot) => trim((string) $slot) !== trim((string) $slotToDelete)
        );

        // Sắp xếp lại danh sách timeslots
        usort($updatedSlots, function ($a, $b) {
            return strtotime($a) - strtotime($b);
        });

        // Gán lại danh sách timeslots
        $scheduleWork->setTimeSlots(array_values($updatedSlots));

        // Persist dữ liệu
        $entityManager->persist($scheduleWork);
        $entityManager->flush();
        $entityManager->refresh($scheduleWork); // Refresh để kiểm tra

        return new JsonResponse(['success' => true]);
    }
    // public function deleteSlot(ScheduleWork $scheduleWork, Request $request, EntityManagerInterface $entityManager): JsonResponse
    // {
    //     $data = json_decode($request->getContent(), true);
    //     $slotToDelete = $data['slot'] ?? null;

    //     if (!$slotToDelete) {
    //         return new JsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ!'], 400);
    //     }

    //     // Lọc bỏ slot cần xóa
    //     $updatedSlots = array_filter(
    //         $scheduleWork->getTimeSlots(),
    //         fn($slot) => trim($slot) !== trim($slotToDelete) // Loại bỏ khoảng trắng dư
    //     );

    //     $scheduleWork->setTimeSlots($updatedSlots);

    //     $entityManager->persist($scheduleWork);
    //     $entityManager->flush();

    //     return new JsonResponse(['success' => true]);
    // }

    // #[Route('/{id}', name: 'app_doctor_schedule_delete', methods: ['POST'])]
    // public function delete(Request $request, ScheduleWork $scheduleWork, EntityManagerInterface $entityManager): Response
    // {
    //     if ($this->isCsrfTokenValid('delete' . $scheduleWork->getId(), $request->getPayload()->getString('_token'))) {
    //         $entityManager->remove($scheduleWork);
    //         $entityManager->flush();
    //     }

    //     return $this->redirectToRoute('app_doctor_schedule_index', [], Response::HTTP_SEE_OTHER);
    // }
}
