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
                'format' => 'dd/MM/yyyy',
                'html5' => false,
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

    // #[Route('/add-slot', name: 'doctor_add_schedule_slot', methods: ['POST', 'GET'])]
    // public function addSlot(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     //  Tạo đối tượng ScheduleWork mới
    //     $scheduleWork = new ScheduleWork();

    //     //  Lấy bác sĩ hiện tại (người dùng)
    //     $doctor = $this->getUser();
    //     if (!$doctor) {
    //         throw new \LogicException('Không tìm thấy bác sĩ.');
    //     }
    //     $scheduleWork->setDoctor($doctor);

    //     //  Tạo danh sách khung giờ làm việc
    //     $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 30);

    //     //  Tạo form
    //     $form = $this->createForm(DoctorScheduleWorkType::class, $scheduleWork, [
    //         'time_slots' => $timeSlots,
    //     ]);
    //     $form->handleRequest($request);

    //     // if ($form->isSubmitted()) {
    //     //     dump($request->request->all());
    //     //     dump($form->getData());
    //     //     die();
    //     // }

    //     //  Xử lý form khi submit
    //     if ($form->isSubmitted()) {
    //         $doctor = $this->getUser();
    //         $scheduleWork->setDoctor($doctor);

    //         $dateString = $form->get('date')->getData(); // Lấy dữ liệu từ form

    //         if (is_string($dateString)) {
    //             $date = \DateTime::createFromFormat('d-m-Y', $dateString); // Format khớp với Symfony
    //         } else {
    //             $date = $dateString;
    //         }

    //         if (!$date instanceof \DateTimeInterface) {
    //             dump('Lỗi: Không thể chuyển đổi ngày làm việc!');
    //         } else {
    //             $scheduleWork->setDate($date);
    //         }

    //         // Fix lỗi status Enum
    //         $status = $form->get('status')->getData();
    //         $scheduleWork->setStatus($status);


    //         $newSlots = $form->get('timeSlots')->getData();
    //         if (!empty($newSlots)) {
    //             $updatedSlots = $scheduleWork->getTimeSlots();
    //             foreach ($newSlots as $slot) {
    //                 if (!in_array($slot, $updatedSlots, true)) {
    //                     $updatedSlots[] = trim($slot);
    //                 }
    //             }
    //             usort($updatedSlots, fn($a, $b) => strtotime(explode('-', $a)[0]) - strtotime(explode('-', $b)[0]));
    //             $scheduleWork->setTimeSlots(array_values($updatedSlots));

    //             // dump($scheduleWork);
    //             // die();

    //             $entityManager->persist($scheduleWork);
    //             $entityManager->flush();

    //             return $this->redirectToRoute('app_doctor_schedule_index');
    //         }
    //     }


    //     return $this->render('doctor/schedule_work/new.html.twig', [
    //         'form' => $form->createView(),
    //     ]);
    // }


}
