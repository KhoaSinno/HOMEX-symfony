<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\ScheduleWork;
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
        // dump($duration); die();

        $timeSlots_10 = $this->scheduleService->generateTimeSlots('07:00', '17:00', 10);
        $timeSlots_15 = $this->scheduleService->generateTimeSlots('07:00', '17:00', 20);
        $combinedTimeSlots = array_merge($timeSlots_10, $timeSlots_15);

        $doctors = $this->userRepository->findByRole('ROLE_DOCTOR');
        $scheduleWork = new ScheduleWork();
        $scheduleWork->setStatus(ScheduleStatus::AVAILABLE);

        $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
            'time_slots' => $combinedTimeSlots,
            'doctors' => $doctors,
        ]);

        $form->handleRequest($request);



        // if ($form->isSubmitted()) {
        //     dump($combinedTimeSlots);
        //     dump($data);
        //     dump($form->get('timeSlots')->getData()); // Kiểm tra dữ liệu form
        //     dump($request->request->all()); // Kiểm tra dữ liệu từ request
        //     die();
        // }

        // if ($form->isSubmitted() && $form->isValid()) {
        //     dump($scheduleWork->getTimeSlots()); // Kiểm tra xem entity có dữ liệu không
        //     // die();
        // }


        if ($form->isSubmitted() && $form->isValid()) {
            $doctor = $scheduleWork->getDoctor();
            $date = $scheduleWork->getDate();
            $status = $form->get('status')->getData();
            $status = $status instanceof ScheduleStatus ? $status : ScheduleStatus::from($status);

            // 🔹 Lấy `timeSlots` từ form và đảm bảo nó là một mảng hợp lệ
            $timeSlots = $scheduleWork->getTimeSlots();
            if (is_string($timeSlots)) {
                $timeSlots = json_decode($timeSlots, true);
            }
            $timeSlots = is_array($timeSlots) ? $timeSlots : [];

            $scheduleWork->setTimeSlots($timeSlots);
            $scheduleWork->setStatus($status);

            // 🔹 Kiểm tra xem bác sĩ này đã có lịch trong ngày đó chưa
            $existingSchedule = $this->em->getRepository(ScheduleWork::class)->findOneBy([
                'doctor' => $doctor,
                'date' => $date,
            ]);

            if ($existingSchedule) {
                $mergedTimeSlots = array_merge($existingSchedule->getTimeSlots(), $scheduleWork->getTimeSlots());
                $newTimeSlots = array_values(array_unique($mergedTimeSlots));
                usort($newTimeSlots, function ($a, $b) {
                    return strtotime(explode('-', $a)[0]) - strtotime(explode('-', $b)[0]);
                });

                $existingSchedule->setTimeSlots($newTimeSlots);
                $existingSchedule->setStatus($status);
                $this->em->flush();
            } else {
                $this->em->persist($scheduleWork);
                $this->em->flush();
            }

            return $this->redirectToRoute('app_schedule_work_index');
        }

        return $this->render('admin/schedule_work/create_schedule.html.twig', [
            'form' => $form->createView(),
        ]);
    }

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
    
        // Lấy danh sách TimeSlots đã chọn từ DB
        $selectedTimeSlots = $scheduleWork->getTimeSlots();
    
        // Tạo danh sách TimeSlots mới (10 phút và 15 phút)
        $timeSlots_10 = $this->scheduleService->generateTimeSlots('07:00', '17:00', 10);
        $timeSlots_15 = $this->scheduleService->generateTimeSlots('07:00', '17:00', 20);
        $combinedTimeSlots = array_merge($timeSlots_10, $timeSlots_15);
    
        // Gộp danh sách mới với danh sách đã chọn trước đó
        $mergedTimeSlots = array_map(fn($slot) => [
            'time' => $slot,
            'checked' => in_array($slot, $selectedTimeSlots),
        ], $combinedTimeSlots);
    
        // Truyền danh sách timeslot vào form
        $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
            'time_slots' => array_column($mergedTimeSlots, 'time'),
        ]);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $status = $form->get('status')->getData();
            $status = $status instanceof ScheduleStatus ? $status : ScheduleStatus::from($status);
    
            // Lấy `timeSlots` từ form và đảm bảo nó là mảng hợp lệ
            $timeSlots = $scheduleWork->getTimeSlots();
            if (is_string($timeSlots)) {
                $timeSlots = json_decode($timeSlots, true);
            }
            $timeSlots = is_array($timeSlots) ? $timeSlots : [];
    
            $scheduleWork->setTimeSlots($timeSlots);
            $scheduleWork->setStatus($status);
    
            // Kiểm tra xem bác sĩ này đã có lịch trong ngày đó chưa (ngoại trừ lịch đang chỉnh sửa)
            $existingSchedule = $entityManager->getRepository(ScheduleWork::class)->findOneBy([
                'doctor' => $doctor,
                'date' => $scheduleWork->getDate(),
            ]);
    
            if ($existingSchedule && $existingSchedule !== $scheduleWork) {
                // Nếu có lịch khác trong ngày, gộp timeSlots
                $mergedTimeSlots = array_merge($existingSchedule->getTimeSlots(), $scheduleWork->getTimeSlots());
                $newTimeSlots = array_values(array_unique($mergedTimeSlots));
                usort($newTimeSlots, function ($a, $b) {
                    return strtotime(explode('-', $a)[0]) - strtotime(explode('-', $b)[0]);
                });
    
                // Cập nhật lịch hiện có với timeSlots gộp
                $existingSchedule->setTimeSlots($newTimeSlots);
                $existingSchedule->setStatus($status);
    
                // Xóa lịch đang chỉnh sửa nếu không muốn giữ lại
                $entityManager->remove($scheduleWork);
                $entityManager->flush();
            } else {
                // Nếu không có lịch khác, chỉ cập nhật lịch hiện tại
                $entityManager->persist($scheduleWork);
                $entityManager->flush();
            }
    
            return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('admin/schedule_work/edit.html.twig', [
            'schedule_work' => $scheduleWork,
            'form' => $form->createView(),
            'doctorId' => $doctorId,
            'date' => $date,
        ]);
    }

    // public function edit(Request $request, ScheduleWork $scheduleWork, EntityManagerInterface $entityManager): Response
    // {
    //     if (!$scheduleWork) {
    //         throw $this->createNotFoundException('Không tìm thấy lịch khám.');
    //     }

    //     $doctor = $scheduleWork->getDoctor(); // Lấy bác sĩ từ lịch khám
    //     $doctorId = $doctor ? $doctor->getId() : null;
    //     $date = $scheduleWork->getDate() ? $scheduleWork->getDate()->format('Y-m-d') : null;

    //     // 🔹 Lấy danh sách TimeSlots đã chọn từ DB
    //     $selectedTimeSlots = $scheduleWork->getTimeSlots();

    //     // 🔹 Tạo danh sách TimeSlots mới (chỉ 10 phút)
    //     $timeSlots_10 = $this->scheduleService->generateTimeSlots('07:00', '17:00', 10);
    //     $timeSlots_15 = $this->scheduleService->generateTimeSlots('07:00', '17:00', 20);
    //     $combinedTimeSlots = array_merge($timeSlots_10, $timeSlots_15);

    //     // 🔹 Gộp danh sách mới với danh sách đã chọn trước đó
    //     $mergedTimeSlots = array_map(fn($slot) => [
    //         'time' => $slot,
    //         'checked' => in_array($slot, $selectedTimeSlots),
    //     ], $combinedTimeSlots);

    //     // dump($mergedTimeSlots);
    //     // die();


    //     // 🔹 Truyền danh sách timeslot dạng chuỗi vào form
    //     $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
    //         'time_slots' => array_column($mergedTimeSlots, 'time'),
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

        return $this->json(['timeSlots' => $updatedSlots, 'selectedTimeSlots' => $selectedTimeSlots]);
    }


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
}
