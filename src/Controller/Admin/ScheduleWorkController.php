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

    // create schedule work
    #[Route('/create', name: 'app_create_schedule', methods: ['GET', 'POST'])]
    public function createSchedule(Request $request): Response
    {
        $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 30);
        $doctors = $this->userRepository->findByRole('ROLE_DOCTOR');
        $scheduleWork = new ScheduleWork();
        $scheduleWork->setStatus(ScheduleStatus::AVAILABLE); // Đảm bảo mặc định

        $form = $this->createForm(ScheduleWorkType::class, $scheduleWork, [
            'time_slots' => $timeSlots,
            'doctors' => $doctors,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Lấy status từ form
            $status = $form->get('status')->getData();

            if (!$status instanceof ScheduleStatus) {
                $status = ScheduleStatus::from($status); // Chuyển đổi nếu cần
            }

            $scheduleWork->setStatus($status);
            $this->em->persist($scheduleWork);
            $this->em->flush();

            return $this->redirectToRoute('app_schedule_work_index');
        }

        return $this->render('admin/schedule_work/create_schedule.html.twig', [
            'form' => $form->createView(),
        ]);
    }


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
        if (!$scheduleWork) {
            throw $this->createNotFoundException('Không tìm thấy lịch khám.');
        }

        // Tạo danh sách thời gian
        $timeSlots = $this->scheduleService->generateTimeSlots('07:00', '17:00', 30);

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
