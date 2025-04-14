<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Review;
use App\Entity\Specialty;
use App\Entity\User;

use App\Entity\ScheduleWork; // Thêm entity ScheduleWork
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

        // Lấy ngày hiện tại
        $today = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $today->setTime(0, 0, 0); // Đặt giờ về 00:00:00

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

            // Lấy lịch làm việc của bác sĩ từ ngày hiện tại trở đi
            $schedules = $entityManager->getRepository(ScheduleWork::class)
                ->createQueryBuilder('s')
                ->where('s.doctor = :doctor')
                ->andWhere('s.date >= :today')
                ->setParameter('doctor', $doctor)
                ->setParameter('today', $today->format('Y-m-d'))
                ->orderBy('s.date', 'ASC')
                ->setMaxResults(7) // Giới hạn 7 ngày tới
                ->getQuery()
                ->getResult();

            // Thay đổi phần xử lý timeSlots trong ChatbotController.php
            $workSchedules = [];
            foreach ($schedules as $schedule) {
                // Kiểm tra cấu trúc của timeSlots
                $timeSlots = $schedule->getTimeSlots();
                $formattedTimeSlots = '';

                // Mảng để lưu các khung giờ theo buổi
                $morningSlots = [];    // Sáng: trước 12:00
                $afternoonSlots = [];  // Chiều: 12:00 - 18:00
                $eveningSlots = [];    // Tối: sau 18:00

                // Xử lý timeSlots tùy thuộc vào cấu trúc của nó
                $slotsArray = [];

                if (is_array($timeSlots)) {
                    $slotsArray = $timeSlots;
                } elseif (is_string($timeSlots)) {
                    // Kiểm tra xem chuỗi có phải là JSON hay không
                    if ($this->isJson($timeSlots)) {
                        // Giải mã chuỗi JSON thành mảng PHP
                        $slotsArray = json_decode($timeSlots, true);
                    } else {
                        // Nếu không phải JSON, có thể là chuỗi được phân cách bằng dấu phẩy
                        $slotsArray = array_map('trim', explode(',', $timeSlots));
                    }
                }

                // Phân loại các khung giờ theo buổi
                if (is_array($slotsArray)) {
                    foreach ($slotsArray as $slot) {
                        // Lấy giờ bắt đầu từ khung giờ (dạng "HH:MM-HH:MM")
                        $startTime = explode('-', $slot)[0] ?? '';
                        $hourPart = explode(':', $startTime)[0] ?? '';

                        if (!empty($hourPart) && is_numeric($hourPart)) {
                            $hour = (int) $hourPart;

                            if ($hour < 12) {
                                $morningSlots[] = $slot;
                            } elseif ($hour >= 12 && $hour < 18) {
                                $afternoonSlots[] = $slot;
                            } else {
                                $eveningSlots[] = $slot;
                            }
                        } else {
                            // Nếu không thể phân tích được giờ, thêm vào buổi sáng
                            $morningSlots[] = $slot;
                        }
                    }
                }

                // Định dạng kết quả theo buổi
                $formattedBySession = [
                    'morning' => !empty($morningSlots) ? implode(', ', $morningSlots) : '',
                    'afternoon' => !empty($afternoonSlots) ? implode(', ', $afternoonSlots) : '',
                    'evening' => !empty($eveningSlots) ? implode(', ', $eveningSlots) : ''
                ];

                $workSchedules[] = [
                    'date' => $schedule->getDate()->format('d/m/Y'),
                    'dayOfWeek' => $this->getDayOfWeek($schedule->getDate()->format('N')),
                    'timeSlotsBySession' => $formattedBySession, // Thêm khung giờ đã phân loại theo buổi
                    'timeSlots' => is_array($slotsArray) ? implode(', ', $slotsArray) : 'Chưa cập nhật' // Giữ nguyên phiên bản cũ để tương thích
                ];
            }

            $doctorDetails[] = [
                'id' => $doctor->getId(),
                'name' => $doctor->getFullname(),
                'specialty' => $specialty ? $specialty->getName() : 'Chưa xác định',
                'qualification' => $doctor->getQualification(),
                'bio' => $doctor->getBio(),
                'consultationFee' => $doctor->getConsultationFee(),
                'averageRating' => $averageRating,
                'workSchedules' => $workSchedules
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

        // Tạo prompt cho Google Gemini với hướng dẫn về lịch làm việc
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
                    <br><strong>Phí khám: [Chi phí khám bệnh] VNĐ</strong>
                    <br>Đánh giá: [Số] sao
                    <br>Lịch làm việc sắp tới:
";

        // Thêm hướng dẫn xử lý trường hợp không có lịch làm việc
        $systemPrompt .= "
    - Nếu mảng workSchedules rỗng, hiển thị: \"Vui lòng liên hệ phòng khám để biết lịch làm việc cụ thể\"
    - Nếu có lịch làm việc, hiển thị theo định dạng:
      <ul>
        <li>[Ngày] ([Thứ])
          <ul>
            <li>Buổi sáng: [Danh sách khung giờ buổi sáng]</li>
            <li>Buổi chiều: [Danh sách khung giờ buổi chiều]</li>
            <li>Buổi tối: [Danh sách khung giờ buổi tối]</li>
          </ul>
        </li>
      </ul>
    - Chỉ hiển thị các buổi có lịch làm việc
";

        $systemPrompt .= "
        </li>
    </ul>
    
    Khi người dùng hỏi về lịch làm việc của bác sĩ, hãy cung cấp thông tin chi tiết lịch làm việc sắp tới.
    
    Thông tin về hệ thống HOMEX:
    - Địa chỉ: 256 Nguyễn Văn Cừ, An Hòa, Ninh Kiều, Cần Thơ
    - Hotline: 1900 8080 
    - Giờ làm việc: Thứ 2 - Thứ 7, từ 8h00 đến 17h00
";

        // Thêm dữ liệu huấn luyện về cách hiển thị thông tin phí khám bệnh
        $systemPrompt .= "
    Ví dụ về cách trả lời khi người dùng hỏi về chi phí khám bệnh:
    
    <p>Chào bạn! Tôi rất vui được cung cấp thông tin về chi phí khám bệnh tại HOMEX.</p>
    
    <p>Chi phí khám bệnh tại HOMEX phụ thuộc vào từng bác sĩ và chuyên khoa. Dưới đây là thông tin chi phí của một số bác sĩ:</p>
    
    <ul>
        <li><strong><a href='http://example.com/doctor/1'>Bác sĩ John Doe</a></strong> - Chuyên khoa Tim mạch
            <br>Chuyên môn: Phẫu thuật Tim mạch
            <br><strong>Phí khám: 300,000 VNĐ</strong>
            <br>Đánh giá: 4.5 sao
        </li>
        <li><strong><a href='http://example.com/doctor/2'>Bác sĩ Anna Lee</a></strong> - Chuyên khoa Thần kinh
            <br>Chuyên môn: Thần kinh học và Chẩn đoán hình ảnh não
            <br><strong>Phí khám: 350,000 VNĐ</strong>
            <br>Đánh giá: 4.8 sao
        </li>
    </ul>
    
    <p>Chi phí trên chỉ bao gồm phí tư vấn khám ban đầu. Các chi phí điều trị, thuốc men hoặc xét nghiệm bổ sung sẽ được thông báo chi tiết sau khi bác sĩ khám.</p>
    
    <p>Để biết thêm thông tin chi tiết hoặc đặt lịch khám, bạn có thể nhấp vào tên bác sĩ hoặc liên hệ trực tiếp qua hotline 1900 8080.</p>
";

        // Thêm ví dụ về trả lời khi có người hỏi về triệu chứng bệnh 
        $systemPrompt .= "
    Ví dụ về cách trả lời khi người dùng hỏi về triệu chứng đau đầu:
    
    <p>Chào bạn! Đau đầu có thể là triệu chứng của nhiều vấn đề sức khỏe khác nhau, từ stress, mệt mỏi đến các vấn đề nghiêm trọng hơn như cao huyết áp hoặc rối loạn thần kinh.</p>
    
    <p>Tôi khuyên bạn nên đặt lịch khám với một bác sĩ chuyên khoa Thần kinh. HOMEX có các bác sĩ sau đây có thể giúp bạn:</p>
    
    <ul>
        <li><strong><a href='http://example.com/doctor/2'>Bác sĩ Anna Lee</a></strong> - Chuyên khoa Thần kinh
            <br>Chuyên môn: Thần kinh học và Chẩn đoán hình ảnh não
            <br><strong>Phí khám: 350,000 VNĐ</strong>
            <br>Đánh giá: 4.8 sao
            <br>Lịch làm việc sắp tới:
            <ul>
                <li>08/04/2025 (Thứ Ba)
                    <ul>
                        <li>Buổi sáng: 07:10-07:20, 07:20-07:30, 07:30-07:40</li>
                        <li>Buổi chiều: 14:00-14:30, 15:00-15:30</li>
                    </ul>
                </li>
            </ul>
        </li>
    </ul>
    
    <p><strong>Lưu ý:</strong> Thông tin này chỉ mang tính chất tham khảo. Lời khuyên của tôi không thể thay thế cho chẩn đoán và điều trị của bác sĩ chuyên môn.</p>
";

        $context = [
            'systemPrompt' => $systemPrompt,
            'doctors' => $doctorDetails,
            'specialties' => $specialtyDetails,
            'baseUrl' => $request->getSchemeAndHttpHost(),
            'question' => $question
        ];

        // Gửi câu hỏi đến Google Gemini
        $response = $geminiService->askQuestion($question, $context);

        // Trích xuất nội dung từ phản hồi
        $answer = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Xin lỗi, tôi không thể trả lời câu hỏi này lúc này. Vui lòng thử lại sau hoặc liên hệ trực tiếp qua hotline 1900 8080 .';

        // Đánh dấu rằng phản hồi chứa HTML và an toàn để hiển thị
        return new JsonResponse([
            'answer' => $answer,
            'isHtml' => true
        ]);
    }

    /**
     * Kiểm tra xem một chuỗi có phải là JSON hợp lệ không
     */
    private function isJson($string): bool
    {
        if (!is_string($string)) {
            return false;
        }

        // Thử giải mã JSON và kiểm tra lỗi
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Hàm chuyển đổi số thứ tự ngày trong tuần sang tên tiếng Việt
     */
    private function getDayOfWeek(int $dayNumber): string
    {
        $daysOfWeek = [
            1 => 'Thứ Hai',
            2 => 'Thứ Ba',
            3 => 'Thứ Tư',
            4 => 'Thứ Năm',
            5 => 'Thứ Sáu',
            6 => 'Thứ Bảy',
            7 => 'Chủ Nhật'
        ];

        return $daysOfWeek[$dayNumber] ?? '';
    }
}
