<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\ScheduleWork;
use App\Entity\User;
use App\Form\ScheduleWorkType;
use App\Enum\ScheduleStatus;
use App\Repository\ScheduleWorkRepository;
use App\Repository\UserRepository;
use App\Service\ScheduleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/schedule_work')]
final class ScheduleWorkController extends AbstractController
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


    #[Route(name: 'app_schedule_work_index', methods: ['GET'])]
    public function index(ScheduleWorkRepository $scheduleWorkRepository): Response
    {
        $doctors = $this->userRepository->findByRole('ROLE_DOCTOR');

        return $this->render('admin/schedule_work/index.html.twig', [
            'schedule_works' => $scheduleWorkRepository->findAll(),
            'doctors' => $doctors,
        ]);
    }

    #[Route('/generate-time-slots-create', name: 'generate_time_slots_create', methods: ['POST'])]
    public function generateTimeSlotsCreate(Request $request): JsonResponse
    {
        $duration = (int) $request->request->get('duration', 10); // Lấy số phút từ request
        $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', $duration);

        return new JsonResponse(['timeSlots' => $timeSlots]);
    }



    // create schedule work
    #[Route('/create', name: 'app_create_schedule', methods: ['GET', 'POST'])]
    public function createSchedule(Request $request): Response
    {
       
        
        
        $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 10);
        $doctors = $this->userRepository->findByRole('ROLE_DOCTOR');
        $scheduleWork = new ScheduleWork();
        $scheduleWork->setStatus(ScheduleStatus::AVAILABLE); // Mặc định là AVAILABLE

        $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
            'time_slots' => $timeSlots,
            'doctors' => $doctors,
        ]);
        $form->handleRequest($request);

        // dump($request->request->all()); // Xem toàn bộ dữ liệu request
        // dump($form->get('timeSlots')->getData()); // Xem dữ liệu trong form
        // die();
        
        // if ($form->isSubmitted()) {
        //     dump($form->get('timeSlots')->getData()); // Dữ liệu nhận từ request
        //     dump($timeSlots); // Danh sách time_slots hợp lệ
        //     die();
        // }
        
        
        if ($form->isSubmitted() && $form->isValid()) {
            // dump($scheduleWork->getTimeSlots());
            // die();

            $doctor = $scheduleWork->getDoctor();
            $date = $scheduleWork->getDate();
            $status = $form->get('status')->getData();
            $status = $status instanceof ScheduleStatus ? $status : ScheduleStatus::from($status);

            $scheduleWork->setStatus($status);

            // 🔹 Kiểm tra xem bác sĩ này đã có lịch trong ngày đó chưa
            $existingSchedule = $this->em->getRepository(ScheduleWork::class)->findOneBy([
                'doctor' => $doctor,
                'date' => $date,
            ]);

            if ($existingSchedule) {
                // 🔹 Nếu đã có lịch, gộp danh sách TimeSlots
                $mergedTimeSlots = array_merge($existingSchedule->getTimeSlots(), $scheduleWork->getTimeSlots());

                // Loại bỏ trùng lặp và sắp xếp lại
                $newTimeSlots = array_values(array_unique($mergedTimeSlots));
                usort($newTimeSlots, function ($a, $b) {
                    return strtotime(explode('-', $a)[0]) - strtotime(explode('-', $b)[0]);
                });

                // Cập nhật lại lịch hiện có
                $existingSchedule->setTimeSlots($newTimeSlots);
                $existingSchedule->setStatus($status);

                $this->em->flush(); // Lưu thay đổi
            } else {
                // 🔹 Nếu chưa có lịch, tạo mới
                $this->em->persist($scheduleWork);
                $this->em->flush();
            }

            return $this->redirectToRoute('app_schedule_work_index');
        }

        return $this->render('admin/schedule_work/create_schedule.html.twig', [
            'form' => $form->createView(),
        ]);
    }



    // // create schedule work
    // #[Route('/create', name: 'app_create_schedule', methods: ['GET', 'POST'])]
    // public function createSchedule(Request $request): Response
    // {
    //     $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 10);
    //     $doctors = $this->userRepository->findByRole('ROLE_DOCTOR');
    //     $scheduleWork = new ScheduleWork();
    //     $scheduleWork->setStatus(ScheduleStatus::AVAILABLE); // Đảm bảo mặc định

    //     $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
    //         'time_slots' => $timeSlots,
    //         'doctors' => $doctors,
    //     ]);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         // Lấy status từ form
    //         $status = $form->get('status')->getData();

    //         if (!$status instanceof ScheduleStatus) {
    //             $status = ScheduleStatus::from($status); // Chuyển đổi nếu cần
    //         }

    //         $scheduleWork->setStatus($status);
    //         $this->em->persist($scheduleWork);
    //         $this->em->flush();

    //         return $this->redirectToRoute('app_schedule_work_index');
    //     }

    //     return $this->render('admin/schedule_work/create_schedule.html.twig', [
    //         'form' => $form->createView(),
    //     ]);
    // }


    // Default func
    // #[Route('/new', name: 'app_schedule_work_new', methods: ['GET', 'POST'])]
    // public function new(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     $scheduleWork = new ScheduleWork();
    //     $form = $this->createForm(ScheduleWorkType::class, $scheduleWork);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->persist($scheduleWork);
    //         $entityManager->flush();

    //         return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('admin/schedule_work/new.html.twig', [
    //         'schedule_work' => $scheduleWork,
    //         'form' => $form,
    //     ]);
    // }

    #[Route('/{id}', name: 'app_schedule_work_show', methods: ['GET'])]
    public function show(ScheduleWork $scheduleWork): Response
    {
        return $this->render('admin/schedule_work/show.html.twig', [
            'schedule_work' => $scheduleWork,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_schedule_work_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ScheduleWork $scheduleWork, EntityManagerInterface $entityManager): Response
    {
        if (!$scheduleWork) {
            throw $this->createNotFoundException('Không tìm thấy lịch khám.');
        }

        $doctor = $scheduleWork->getDoctor(); // Lấy bác sĩ từ lịch khám
        $doctorId = $doctor ? $doctor->getId() : null;
        $date = $scheduleWork->getDate() ? $scheduleWork->getDate()->format('Y-m-d') : null;

        // 🔹 Lấy danh sách TimeSlots đã chọn từ DB
        $selectedTimeSlots = $scheduleWork->getTimeSlots(); 

        // 🔹 Tạo danh sách TimeSlots mới (chỉ 10 phút)
        $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 10);

        // 🔹 Gộp danh sách mới với danh sách đã chọn trước đó
        $mergedTimeSlots = array_map(fn($slot) => [
            'time' => $slot,
            'checked' => in_array($slot, $selectedTimeSlots),
        ], $timeSlots);

        // dump($mergedTimeSlots);
        // die();
        

        // 🔹 Truyền danh sách timeslot dạng chuỗi vào form
        $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
            'time_slots' => array_column($mergedTimeSlots, 'time'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/schedule_work/edit.html.twig', [
            'schedule_work' => $scheduleWork,
            'form' => $form->createView(),
            'doctorId' => $doctorId,
            'date' => $date,
        ]);
    }

    #[Route('/generate-time-slots-edit', name: 'generate_time_slots_edit', methods: ['POST'])]
    public function generateTimeSlotsEdit(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $duration = (int) $request->request->get('duration', 10);
        $doctorId = $request->request->get('doctorId');
        $date = $request->request->get('date');

        if (!$doctorId || !$date) {
            return $this->json(['error' => 'Thiếu doctorId hoặc date'], Response::HTTP_BAD_REQUEST);
        }

        // 🔹 Lấy lịch khám của bác sĩ trong ngày cụ thể
        $scheduleWork = $em->getRepository(ScheduleWork::class)->findOneBy([
            'doctor' => $doctorId,
            'date' => new \DateTime($date),
        ]);

        // dump($scheduleWork);
        // die();

        if (!$scheduleWork) {
            return $this->json(['timeSlots' => []]); // Không có lịch thì trả về mảng rỗng
        }

        // 🔹 Lấy danh sách time slots đã có
        $selectedTimeSlots = $scheduleWork->getTimeSlots() ?? [];

        // 🔹 Tạo danh sách time slots theo duration
        $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', $duration);

        // 🔹 Gắn cờ `checked` cho time slots đã có trong DB
        $updatedSlots = array_map(fn($slot) => [
            'time' => $slot,
            'checked' => in_array($slot, $selectedTimeSlots),
        ], $timeSlots);

        return $this->json(['timeSlots' => $updatedSlots, 'selectedTimeSlots' => $selectedTimeSlots, 'ok'=>true]);
    }



    // point

    // #[Route('/generate-time-slots', name: 'generate_time_slots', methods: ['POST'])]
    // public function generateTimeSlots(Request $request, EntityManagerInterface $em): JsonResponse
    // {
    //     $duration = (int) $request->request->get('duration', 10);
    //     $doctorId = $request->request->get('doctorId');
    //     $date = new \DateTime($request->request->get('date')); // Chuyển date sang DateTime

    //     if (!$doctorId) {
    //         return $this->json(['error' => 'Thiếu doctorId'], Response::HTTP_BAD_REQUEST);
    //     }

    //     // 🔥 Lấy bác sĩ từ database
    //     $doctor = $em->getRepository(User::class)->find($doctorId);
    //     if (!$doctor) {
    //         return $this->json(['error' => 'Không tìm thấy bác sĩ'], Response::HTTP_NOT_FOUND);
    //     }

    //     // 🔥 Lấy lịch làm việc của bác sĩ theo ngày
    //     $scheduleWork = $em->getRepository(ScheduleWork::class)->findOneBy([
    //         'doctor' => $doctor,
    //         'date' => $date
    //     ]);

    //     if (!$scheduleWork) {
    //         return $this->json(['error' => 'Không tìm thấy lịch làm việc'], Response::HTTP_NOT_FOUND);
    //     }

    //     // 🚀 Lấy danh sách time slots từ lịch đã lưu
    //     $existingSlots = $scheduleWork->getTimeSlots();

    //     // 🚀 Tạo danh sách time slots theo duration
    //     $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', $duration);

    //     // 🚀 Gắn cờ `checked`
    //     $updatedSlots = array_map(function ($slot) use ($existingSlots) {
    //         return [
    //             'time' => $slot,
    //             'checked' => in_array($slot, $existingSlots),
    //         ];
    //     }, $timeSlots);

    //     return $this->json(['timeSlots' => $updatedSlots]);
    // }

    // #[Route('/{id}/edit', name: 'app_schedule_work_edit', methods: ['GET', 'POST'])]

    // public function edit(Request $request, ScheduleWork $scheduleWork, EntityManagerInterface $entityManager): Response
    // {
    //     if (!$scheduleWork) {
    //         throw $this->createNotFoundException('Không tìm thấy lịch khám.');
    //     }

    //     $doctor = $scheduleWork->getDoctor();
    //     $doctorId = $doctor ? $doctor->getId() : null;
    //     $date = $scheduleWork->getDate() ? $scheduleWork->getDate()->format('Y-m-d') : null;

    //     // 🔹 Lấy danh sách TimeSlots đã chọn từ database
    //     $selectedTimeSlots = $scheduleWork->getTimeSlots();

    //     // 🔹 Tạo danh sách tất cả TimeSlots 10 phút
    //     $allTimeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 10);

    //     // 🔹 Gắn cờ checked nếu TimeSlot đã có trong DB
    //     $updatedTimeSlots = array_map(function ($slot) use ($selectedTimeSlots) {
    //         return [
    //             'time' => $slot,
    //             'checked' => in_array($slot, $selectedTimeSlots), // Nếu có trong DB thì checked ✅
    //         ];
    //     }, $allTimeSlots);

    //     // 🔹 Tạo form và truyền danh sách TimeSlots cập nhật
    //     $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
    //         'time_slots' => $updatedTimeSlots, // Truyền danh sách đã xử lý vào form
    //     ]);

    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->flush();
    //         return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('admin/schedule_work/edit.html.twig', [
    //         'schedule_work' => $scheduleWork,
    //         'form' => $form->createView(),
    //         'doctorId' => $doctorId,
    //         'date' => $date,
    //     ]);
    // }

    // older

    // public function edit(Request $request, ScheduleWork $scheduleWork, EntityManagerInterface $entityManager): Response
    // {
    //     // dump($request->request->all());
    //     // die();

    //     $doctor = $scheduleWork->getDoctor(); // 🔥 Giả sử có quan hệ ManyToOne với User (Bác sĩ)
    //     $doctorId = $doctor ? $doctor->getId() : null;
    //     // lấy date từ scheduleWork
    //     // $date = $scheduleWork->getDate();
    //     $date = $scheduleWork->getDate() ? $scheduleWork->getDate()->format('Y-m-d') : null;


    //     if (!$scheduleWork) {
    //         throw $this->createNotFoundException('Không tìm thấy lịch khám.');
    //     }

    //     // 🔹 Lấy danh sách TimeSlots đã chọn trước đó
    //     $selectedTimeSlots = $scheduleWork->getTimeSlots(); // Mảng từ database

    //     // 🔹 Tạo danh sách TimeSlots mới (dựa trên slotDuration)
    //     $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 10);

    //     // 🔹 Gộp danh sách mới với danh sách đã chọn trước đó
    //     $mergedTimeSlots = array_unique(array_merge($timeSlots, $selectedTimeSlots));

    //     // 🔹 Sắp xếp lại theo thời gian
    //     usort($mergedTimeSlots, function ($a, $b) {
    //         return strtotime(explode('-', $a)[0]) - strtotime(explode('-', $b)[0]);
    //     });

    //     // 🔹 Tạo form và truyền danh sách đã cập nhật
    //     $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
    //         'time_slots' => $mergedTimeSlots, // Truyền danh sách đã gộp vào form
    //     ]);

    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->flush();
    //         return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('admin/schedule_work/edit.html.twig', [
    //         'schedule_work' => $scheduleWork,
    //         'form' => $form->createView(),
    //         'doctorId' => $doctorId,
    //         'date' => $date,
    //     ]);
    // }

    // #[Route('/{id}/edit', name: 'app_schedule_work_edit', methods: ['GET', 'POST'])]
    // public function edit(Request $request, ScheduleWork $scheduleWork, EntityManagerInterface $entityManager): Response
    // {
    //     if (!$scheduleWork) {
    //         throw $this->createNotFoundException('Không tìm thấy lịch khám.');
    //     }

    //     // Tạo danh sách thời gian
    //     $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 30);

    //     // Tạo form và truyền thời gian vào
    //     $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
    //         'time_slots' => $timeSlots,  // Truyền 'time_slots' vào form
    //     ]);

    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->flush();
    //         return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('admin/schedule_work/edit.html.twig', [
    //         'schedule_work' => $scheduleWork,
    //         'form' => $form->createView(),
    //     ]);
    // }

    #[Route('/{id}', name: 'app_schedule_work_delete', methods: ['POST'])]
    public function delete(Request $request, ScheduleWork $scheduleWork, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $scheduleWork->getId(), $request->getPayload()->getString('_token'))) {
            $doctor = $scheduleWork->getDoctor();
            $scheduleDate = $scheduleWork->getDate();

            if (!$scheduleDate instanceof \DateTimeInterface) {
                throw new \Exception('Invalid date format');
            }

            $formattedScheduleDate = $scheduleDate->format('Y-m-d'); // Chỉ lấy ngày (YYYY-MM-DD)

            // 🔹 Kiểm tra xem có Appointment nào của bác sĩ trùng ngày không
            $qb = $entityManager->createQueryBuilder();
            $qb->select('COUNT(a.id)')
                ->from(Appointment::class, 'a')
                ->where('a.doctor = :doctor')
                ->andWhere("SUBSTRING(a.appointmentDate, 1, 10) = :scheduleDate") // Lấy phần ngày (YYYY-MM-DD)
                ->setParameter('doctor', $doctor)
                ->setParameter('scheduleDate', $formattedScheduleDate);

            $appointmentCount = $qb->getQuery()->getSingleScalarResult();

            // dump($appointmentCount);
            // die();

            if ($appointmentCount > 0) {
                // 🚫 Không thể xóa vì có cuộc hẹn trùng ngày
                $this->addFlash('danger', 'Không thể xóa vì có cuộc hẹn trong lịch này.');
                return $this->redirectToRoute('app_schedule_work_index');
            }

            // ✅ Nếu không có Appointment nào trùng lịch thì xóa lịch làm việc
            $entityManager->remove($scheduleWork);
            $entityManager->flush();

            $this->addFlash('success', 'Lịch làm việc đã được xóa thành công.');
        }

        return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
    }







    // #[Route('/{id}', name: 'app_schedule_work_delete', methods: ['POST'])]
    // public function delete(Request $request, ScheduleWork $scheduleWork, EntityManagerInterface $entityManager): Response
    // {
    //     if ($this->isCsrfTokenValid('delete' . $scheduleWork->getId(), $request->getPayload()->getString('_token'))) {
    //         $entityManager->remove($scheduleWork);
    //         $entityManager->flush();
    //     }

    //     return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
    // }
}
