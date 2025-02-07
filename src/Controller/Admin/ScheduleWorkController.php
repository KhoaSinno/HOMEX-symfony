<?php

namespace App\Controller\Admin;

use App\Entity\ScheduleWork;
use App\Entity\User;
use App\Form\ScheduleWorkType;
use App\Enum\ScheduleStatus;
use App\Repository\ScheduleWorkRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/schedule_work')]
final class ScheduleWorkController extends AbstractController
{
    private $userRepository;
    private $em;

    function __construct(UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->em = $em;
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

    // Show all schedule work
    // #[Route('/{doctorId}/{date}', name: 'app_view_schedule')]
    // public function viewSchedule(int $doctorId, string $date, EntityManagerInterface $em): Response
    // {
    //     $schedules = $em->getRepository(ScheduleWork::class)->findBy([
    //         'doctor' => $doctorId,
    //         'date' => new \DateTime($date),
    //     ]);

    //     return $this->render('admin/schedule_work/view_schedule.html.twig', [
    //         'schedules' => $schedules,
    //         'date' => $date,
    //     ]);
    // }

    // create schedule work
    #[Route('/create', name: 'app_create_schedule', methods: ['GET', 'POST'])]
    public function createSchedule(Request $request): Response
    {
        $timeSlots = $this->generateTimeSlots('07:00', '17:00', 30);
        $doctors = $this->userRepository->findByRole('ROLE_DOCTOR');
        $scheduleWork = new ScheduleWork();

        $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
            'time_slots' => $timeSlots,
            'doctors' => $doctors,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $scheduleWork = $form->getData();
            // Chuyển đổi chuỗi status thành enum
            // $status = $form->get('status')->getData();
            // $scheduleWork->setStatus(ScheduleStatus::from($status));

            $status = $form->get('status')->getData(); // Trả về Enum nếu form được thiết lập đúng
            $scheduleWork->setStatus($status); // Không cần chuyển đổi nữa

            $this->em->persist($scheduleWork);
            $this->em->flush();

            return $this->redirectToRoute('app_schedule_work_index');
        }

        return $this->render('admin/schedule_work/create_schedule.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    // public function createSchedule(Request $request, EntityManagerInterface $em): Response
    // {
    //     // $doctors = $em->getRepository(User::class)->findBy(['role' => 'doctor']);
    //     $doctors = $this->userRepository->findByRole('ROLE_DOCTOR');

    //     if ($request->isMethod('POST')) {
    //         $doctorId = $request->request->get('doctor_id');
    //         $date = $request->request->get('date');
    //         $maxPatients = $request->request->get('max_patients', 1);
    //         $timeSlots = $request->request->get('time_slots');
    //         if (!$timeSlots) {
    //             $timeSlots = [];
    //         }

    //         $doctor = $em->getRepository(User::class)->find($doctorId);

    //         foreach ($timeSlots as $slot) {
    //             [$timeStart, $timeEnd] = explode('-', $slot);

    //             $scheduleWork = new ScheduleWork();
    //             // $scheduleWork->setDoctor($doctor);
    //             $scheduleWork->setDate(new \DateTime($date));
    //             $scheduleWork->setTimeStart(new \DateTime($timeStart));
    //             $scheduleWork->setTimeEnd(new \DateTime($timeEnd));
    //             $scheduleWork->setMaxPatient($maxPatients);
    //             $scheduleWork->setStatus(ScheduleStatus::AVAILABLE);

    //             $em->persist($scheduleWork);
    //         }

    //         $em->flush();

    //         return $this->redirectToRoute('admin_schedule');
    //     }

    //     return $this->render('admin/schedule_work/create_schedule.html.twig', [
    //         'doctors' => $doctors,
    //         'time_slots' => $this->generateTimeSlots('07:00', '17:00', 30),
    //     ]);
    // }
    private function generateTimeSlots(string $startTime, string $endTime, int $intervalMinutes): array
    {
        $timeSlots = [];

        // Đảm bảo sử dụng múi giờ cụ thể (ví dụ: 'Asia/Ho_Chi_Minh')
        $timezone = new \DateTimeZone('Asia/Ho_Chi_Minh');

        // Cung cấp múi giờ khi tạo DateTime
        $currentTime = new \DateTime($startTime, $timezone);
        $endTime = new \DateTime($endTime, $timezone);

        while ($currentTime < $endTime) {
            $slotStart = clone $currentTime;
            $slotEnd = (clone $currentTime)->modify("+$intervalMinutes minutes");
            $timeSlots[] = $slotStart->format('H:i') . '-' . $slotEnd->format('H:i');
            $currentTime = $slotEnd;
        }

        return $timeSlots;
    }

    // private function generateTimeSlots(string $startTime, string $endTime, int $intervalMinutes): array
    // {
    //     $timeSlots = [];
    //     $currentTime = new \DateTime($startTime);
    //     $endTime = new \DateTime($endTime);

    //     while ($currentTime < $endTime) {
    //         $slotStart = clone $currentTime;
    //         $slotEnd = (clone $currentTime)->modify("+$intervalMinutes minutes");
    //         $timeSlots[] = $slotStart->format('H:i') . '-' . $slotEnd->format('H:i');
    //         $currentTime = $slotEnd;
    //     }

    //     return $timeSlots;
    // }

    // Default func
    #[Route('/new', name: 'app_schedule_work_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $scheduleWork = new ScheduleWork();
        $form = $this->createForm(ScheduleWorkType::class, $scheduleWork);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($scheduleWork);
            $entityManager->flush();

            return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/schedule_work/new.html.twig', [
            'schedule_work' => $scheduleWork,
            'form' => $form,
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
        // Tạo danh sách thời gian
        $timeSlots = $this->generateTimeSlots('07:00', '17:00', 30);

        // Tạo form và truyền thời gian vào
        $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
            'time_slots' => $timeSlots,  // Truyền 'time_slots' vào form
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/schedule_work/edit.html.twig', [
            'schedule_work' => $scheduleWork,
            'form' => $form->createView(),
        ]);
    }

    // public function edit(Request $request, ScheduleWork $scheduleWork, EntityManagerInterface $entityManager): Response
    // {
    //     $form = $this->createForm(ScheduleWorkType::class, $scheduleWork);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->flush();

    //         return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('admin/schedule_work/edit.html.twig', [
    //         'schedule_work' => $scheduleWork,
    //         'form' => $form,
    //     ]);
    // }


    #[Route('/{id}', name: 'app_schedule_work_delete', methods: ['POST'])]
    public function delete(Request $request, ScheduleWork $scheduleWork, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $scheduleWork->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($scheduleWork);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_schedule_work_index', [], Response::HTTP_SEE_OTHER);
    }
}
