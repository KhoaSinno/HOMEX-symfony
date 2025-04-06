<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleGeminiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function askQuestion(string $question, array $context = []): array
    {
        if (!is_string($this->apiKey)) {
            throw new \InvalidArgumentException('API key must be a string, got ' . gettype($this->apiKey));
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
        $url .= '?key=' . $this->apiKey;

        $systemPrompt = $context['systemPrompt'] ?? '';
        $doctorDetails = $context['doctors'] ?? [];
        $specialtyDetails = $context['specialties'] ?? [];

        // Tạo prompt kết hợp thông tin hệ thống và câu hỏi
        $fullPrompt = "$systemPrompt\n\n";
        $fullPrompt .= "DANH SÁCH BÁC SĨ:\n";

        // Giới hạn số lượng bác sĩ trong prompt để tránh quá dài
        $limitedDoctors = array_slice($doctorDetails, 0, 10);
        foreach ($limitedDoctors as $doctor) {
            $fullPrompt .= "- Bác sĩ {$doctor['name']}, Chuyên khoa: {$doctor['specialty']}, ";
            $fullPrompt .= "Chuyên môn: {$doctor['qualification']}, Đánh giá: " . number_format($doctor['averageRating'], 1) . "/5\n";
        }

        $fullPrompt .= "\nDANH SÁCH CHUYÊN KHOA:\n";
        foreach ($specialtyDetails as $specialty) {
            $fullPrompt .= "- {$specialty['name']}, Phòng: {$specialty['clinicNumber']}\n";
        }

        $fullPrompt .= "\nCâu hỏi của bệnh nhân: $question\n";
        $fullPrompt .= "\nHãy trả lời câu hỏi trên bằng tiếng Việt với phong cách của một trợ lý y tế chuyên nghiệp. Nếu là triệu chứng bệnh, hãy đề xuất bác sĩ chuyên khoa phù hợp từ danh sách trên.";

        $contentParts = [['text' => $fullPrompt]];

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'contents' => [
                    [
                        'parts' => $contentParts,
                    ],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 1000,
                    'temperature' => 0.3, // Giá trị thấp hơn để giảm tính ngẫu nhiên
                    'topP' => 0.9,
                    'topK' => 40,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_ONLY_HIGH',
                    ],
                ],
            ],
        ]);

        return $response->toArray();
    }
}
