<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Review;
use App\Entity\Specialty;
use App\Entity\User;
use App\Service\GoogleGeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Parsedown;

class ChatbotController extends AbstractController
{
    #[Route('/api/chatbot', name: 'chatbot_ask', methods: ['POST'])]
    public function ask(Request $request, GoogleGeminiService $geminiService, \Doctrine\ORM\EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $question = $data['question'] ?? 'Làm thế nào để đặt lịch khám bệnh?';

        // Lấy dữ liệu từ hệ thống để làm context
        $doctors = $entityManager->getRepository(User::class)->findByRole('ROLE_DOCTOR');
        $specialties = $entityManager->getRepository(Specialty::class)->findAll();

        // Tạo danh sách bác sĩ có thông tin chi tiết hơn
        $doctorDetails = [];
        foreach ($doctors as $doctor) {
            $specialty = $doctor->getSpecialty();
            $reviews = $doctor->getReviews();

            // Tính điểm đánh giá trung bình
            $averageRating = 0;
            if ($reviews && count($reviews) > 0) {
                $totalRating = array_reduce($reviews->toArray(), function ($sum, $review) {
                    $rating = $review->getRating();
                    return $sum + ($rating !== null ? $rating : 0);
                }, 0);
                $averageRating = $totalRating / count($reviews);
            }

            $doctorDetails[] = [
                'id' => $doctor->getId(),
                'name' => $doctor->getFullname(),
                'specialty' => $specialty ? $specialty->getName() : 'Chưa xác định',
                'qualification' => $doctor->getQualification(),
                'bio' => $doctor->getBio(),
                'consultationFee' => $doctor->getConsultationFee(),
                'averageRating' => $averageRating
            ];
        }

        // Tạo danh sách chuyên khoa với các thông tin chi tiết
        $specialtyDetails = [];
        foreach ($specialties as $specialty) {
            $specialtyDetails[] = [
                'id' => $specialty->getId(),
                'name' => $specialty->getName(),
                'clinicNumber' => $specialty->getClinicNumber()
            ];
        }

        // Tạo prompt cho Google Gemini với hướng dẫn cụ thể
        $systemPrompt = "
            Bạn là trợ lý ảo y tế của hệ thống đặt lịch khám bệnh HOMEX.
            
            Hướng dẫn quan trọng:
            1. Trả lời bằng tiếng Việt, với phong cách thân thiện và chuyên nghiệp.
            2. Sử dụng định dạng HTML để hiển thị nội dung (không sử dụng Markdown).
            3. Khi đề cập đến bác sĩ, LUÔN sử dụng thẻ <a> với href để tạo liên kết đến trang bác sĩ.
            4. Khi liệt kê danh sách, sử dụng thẻ <ul> và <li>.
            5. Làm nổi bật thông tin quan trọng bằng thẻ <strong>.
            6. Phân đoạn câu trả lời bằng thẻ <p>.
            
            Khi liệt kê bác sĩ, hãy theo mẫu:
            <ul>
                <li><strong><a href=\"URL_PROFILE_BAC_SI\">Bác sĩ [Tên]</a></strong> - Chuyên khoa [Tên chuyên khoa]
                    <br>Chuyên môn: [Chuyên môn]
                    <br>Đánh giá: [Số] sao
                </li>
            </ul>
            
            Thông tin về hệ thống HOMEX:
            - Địa chỉ: 256 Nguyễn Văn Cừ, An Hòa, Ninh Kiều, Cần Thơ
            - Hotline: 1900 123 456
            - Giờ làm việc: Thứ 2 - Thứ 7, từ 8h00 đến 17h00
        ";

        $context = [
            'systemPrompt' => $systemPrompt,
            'doctors' => $doctorDetails,
            'specialties' => $specialtyDetails,
            'baseUrl' => $request->getSchemeAndHttpHost(), // Thêm base URL
            'question' => $question
        ];

        // Gửi câu hỏi đến Google Gemini
        $response = $geminiService->askQuestion($question, $context);

        // Trích xuất nội dung từ phản hồi
        $answer = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Xin lỗi, tôi không thể trả lời câu hỏi này lúc này. Vui lòng thử lại sau hoặc liên hệ trực tiếp qua hotline 1900 123 456.';

        // Đánh dấu rằng phản hồi chứa HTML và an toàn để hiển thị
        return new JsonResponse([
            'answer' => $answer,
            'isHtml' => true
        ]);
    }
}
