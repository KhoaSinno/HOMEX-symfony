<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ReviewController extends AbstractController
{
    #[Route('/admin/reviews', name: 'admin_reviews')]
    public function index(EntityManagerInterface $em)
    {
        $reviews = $em->getRepository(Review::class)->findAll();

        return $this->render('admin/review/index.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    #[Route('/admin/reviews/delete/{id}', name: 'admin_reviews_delete')]
    public function delete(int $id, EntityManagerInterface $em)
    {
        $review = $em->getRepository(Review::class)->find($id);

        if ($review) {
            $em->remove($review);
            $em->flush();
            $this->addFlash('success', 'Đánh giá đã được xóa.');
        }

        return $this->redirectToRoute('admin_reviews');
    }

    #[Route('/submit-review/{doctorId}', name: 'app_submit_review', methods: ['POST'])]
    public function submitReview(
        int $doctorId,
        Request $request,
        EntityManagerInterface $em
    ) {
        $doctor = $em->getRepository(User::class)->find($doctorId);
        $patient = $this->getUser();

        if (!$doctor || !$patient) {
            throw $this->createNotFoundException('Bác sĩ hoặc bệnh nhân không tồn tại.');
        }

        // Kiểm tra quyền
        if (!in_array('ROLE_PATIENT', $patient->getRoles())) {
            $this->addFlash('error', 'Bạn không có quyền đánh giá.');
            return $this->redirectToRoute('app_doctor_profile', ['id' => $doctorId]);
        }

        // Lấy số lượng lịch hẹn có trạng thái "completed" của bệnh nhân với bác sĩ này
        $completedAppointments = $em->getRepository(Appointment::class)->count([
            'doctor' => $doctor,
            'patient' => $patient,
            'status' => 'completed',
        ]);

        // Lấy số lượng đánh giá của bệnh nhân cho bác sĩ này
        $existingReviews = $em->getRepository(Review::class)->count([
            'doctor' => $doctor,
            'patient' => $patient,
        ]);

        // Nếu số lượng đánh giá bằng số lượng lịch hẹn "completed", không cho phép đánh giá thêm
        if ($existingReviews >= $completedAppointments) {
            $this->addFlash('error', 'Bạn đã hoàn thành đánh giá rồi.');
            return $this->redirectToRoute('app_doctor_profile', ['id' => $doctorId]);
        }

        // Lấy dữ liệu từ form
        $rating = $request->request->get('rating');
        $comment = $request->request->get('comment');

        // Tạo đánh giá mới
        $review = new Review();
        $review->setDoctor($doctor);
        $review->setPatient($patient);
        $review->setRating($rating);
        $review->setComment($comment);
        $review->setCreatedAt(new \DateTime());

        $em->persist($review);
        $em->flush();

        $this->addFlash('success', 'Đánh giá của bạn đã được gửi.');

        return $this->redirectToRoute('app_doctor_profile', ['id' => $doctorId]);
    }

    // #[Route('/submit-review/{doctorId}', name: 'app_submit_review', methods: ['POST'])]
    // public function submitReview(
    //     int $doctorId,
    //     Request $request,
    //     EntityManagerInterface $em
    // ) {
    //     $doctor = $em->getRepository(User::class)->find($doctorId);
    //     $patient = $this->getUser();

    //     if (!$doctor || !$patient) {
    //         throw $this->createNotFoundException('Bác sĩ hoặc bệnh nhân không tồn tại.');
    //     }

    //     // Kiểm tra quyền
    //     if (!in_array('ROLE_PATIENT', $patient->getRoles()) && !in_array('ROLE_DOCTOR', $patient->getRoles())) {
    //         $this->addFlash('error', 'Bạn không có quyền đánh giá.');
    //         return $this->redirectToRoute('app_doctor_profile', ['id' => $doctorId]);
    //     }

    //     $rating = $request->request->get('rating');
    //     $comment = $request->request->get('comment');

    //     $review = new Review();
    //     $review->setDoctor($doctor);
    //     $review->setPatient($patient);
    //     $review->setRating($rating);
    //     $review->setComment($comment);
    //     $review->setCreatedAt(new \DateTime());

    //     $em->persist($review);
    //     $em->flush();

    //     $this->addFlash('success', 'Đánh giá của bạn đã được gửi.');

    //     return $this->redirectToRoute('app_doctor_profile', ['id' => $doctorId]);
    // }
}
