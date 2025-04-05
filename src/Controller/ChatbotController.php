<?php

namespace App\Controller;

use App\Entity\Specialty;
use App\Entity\User;
use App\Service\GoogleGeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    #[Route('/api/chatbot', name: 'chatbot_ask', methods: ['POST'])]
    public function ask(Request $request, GoogleGeminiService $geminiService, \Doctrine\ORM\EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $question = $data['question'] ?? 'How can I book an appointment with a doctor?';

        // Lấy dữ liệu từ hệ thống để làm context
        $context = [
            'doctors' => $entityManager->getRepository(User::class)->findByRole('ROLE_DOCTOR'),
            'specialties' => $entityManager->getRepository(Specialty::class)->findAll(),
        ];

        // Gửi câu hỏi đến Google Gemini
        $response = $geminiService->askQuestion($question, $context);

        // Trích xuất nội dung từ phản hồi
        $answer = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Không có câu trả lời phù hợp.';

        return new JsonResponse(['answer' => $answer]);
    }
}
