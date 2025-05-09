<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GoogleGeminiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(HttpClientInterface $httpClient, string $apiKey, UrlGeneratorInterface $urlGenerator)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->urlGenerator = $urlGenerator;
    }

    public function askQuestion(string $question, array $context = []): array
    {
        if (!is_string($this->apiKey)) {
            throw new \InvalidArgumentException('API key must be a string, got ' . gettype($this->apiKey));
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
        // $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
        $url .= '?key=' . $this->apiKey;

        $systemPrompt = $context['systemPrompt'] ?? '';
        $doctorDetails = $context['doctors'] ?? [];
        $specialtyDetails = $context['specialties'] ?? [];

        // Chuẩn bị base URL cho các link
        $baseUrl = $context['baseUrl'] ?? '';

        // Tạo prompt kết hợp thông tin hệ thống và câu hỏi
        $fullPrompt = "$systemPrompt\n\n";
        $fullPrompt .= "DANH SÁCH BÁC SĨ:\n";

        // Thêm thông tin về các bác sĩ kèm link profile và lịch làm việc
        $limitedDoctors = array_slice($doctorDetails, 0, 10);
        foreach ($limitedDoctors as $doctor) {
            // Tạo URL cho mỗi bác sĩ
            $doctorUrl = $this->urlGenerator->generate(
                'app_doctor_profile',
                ['id' => $doctor['id']],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Định dạng phí khám
            $consultationFee = number_format((float)$doctor['consultationFee'], 0, ',', '.') . ' VNĐ';

            $fullPrompt .= "- Bác sĩ {$doctor['name']} (ID: {$doctor['id']}, URL: {$doctorUrl}), ";
            $fullPrompt .= "Chuyên khoa: {$doctor['specialty']}, ";
            $fullPrompt .= "Chuyên môn: {$doctor['qualification']}, ";
            $fullPrompt .= "Phí khám: {$consultationFee}, ";
            $fullPrompt .= "Đánh giá: " . number_format($doctor['averageRating'], 1) . "/5\n";

            // Thêm thông tin về lịch làm việc
            if (!empty($doctor['workSchedules'])) {
                $fullPrompt .= "  Lịch làm việc:\n";
                foreach ($doctor['workSchedules'] as $schedule) {
                    $fullPrompt .= "  - {$schedule['date']} ({$schedule['dayOfWeek']}):\n";

                    // Hiển thị theo buổi
                    if (!empty($schedule['timeSlotsBySession'])) {
                        if (!empty($schedule['timeSlotsBySession']['morning'])) {
                            $fullPrompt .= "    + Buổi sáng: {$schedule['timeSlotsBySession']['morning']}\n";
                        }

                        if (!empty($schedule['timeSlotsBySession']['afternoon'])) {
                            $fullPrompt .= "    + Buổi chiều: {$schedule['timeSlotsBySession']['afternoon']}\n";
                        }

                        if (!empty($schedule['timeSlotsBySession']['evening'])) {
                            $fullPrompt .= "    + Buổi tối: {$schedule['timeSlotsBySession']['evening']}\n";
                        }
                    } else {
                        // Fallback nếu không có dữ liệu phân loại theo buổi
                        $fullPrompt .= "    + Các khung giờ: {$schedule['timeSlots']}\n";
                    }
                }
            } else {
                $fullPrompt .= "  Lịch làm việc: Vui lòng liên hệ phòng khám để biết lịch làm việc cụ thể\n";
            }
            $fullPrompt .= "\n";
        }

        $fullPrompt .= "\nDANH SÁCH CHUYÊN KHOA:\n";
        foreach ($specialtyDetails as $specialty) {
            $fullPrompt .= "- {$specialty['name']}, Phòng: {$specialty['clinicNumber']}\n";
        }

        $fullPrompt .= "\nCâu hỏi của bệnh nhân: $question\n";
        $fullPrompt .= "\nHướng dẫn định dạng:
1. Khi liệt kê bác sĩ, tạo một thẻ HTML <a> với href dẫn đến URL của bác sĩ. Ví dụ: <a href=\"URL_CỦA_BÁC_SĨ\">Tên bác sĩ</a>
2. Trả lời bằng tiếng Việt với phong cách của một trợ lý y tế chuyên nghiệp.
3. Nếu đề cập đến triệu chứng bệnh, hãy đề xuất bác sĩ chuyên khoa phù hợp từ danh sách trên kèm link.
4. Sử dụng các thẻ HTML để định dạng kết quả (<p>, <strong>, <ul>, <li>, <a>).";

        $contentParts = [['text' => $fullPrompt]];

        // Thêm logic thử lại khi gặp lỗi
        $maxRetries = 5;
        $retryCount = 0;
        $lastException = null;

        while ($retryCount < $maxRetries) {
            try {
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
                            'temperature' => 0.3,
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
            } catch (\Exception $e) {
                $lastException = $e;
                $retryCount++;

                // Tạm dừng tăng dần trước khi thử lại
                sleep($retryCount * 2);
            }
        }

        // Nếu vẫn thất bại sau tất cả các lần thử lại, trả về kết quả dự phòng
        return [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => '<p>Xin lỗi, dịch vụ chatbot hiện đang tạm thời gặp sự cố. Vui lòng thử lại sau hoặc liên hệ với chúng tôi qua hotline 1900 8080.</p>'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
