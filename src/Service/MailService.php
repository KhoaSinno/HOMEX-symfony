<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class MailService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendAppointmentConfirmation(string $toEmail, string $patientName, string $appointmentDate, string $doctorName)
    {
        $email = (new Email())
            ->from(new Address('ntakhoa.work@gmail.com', 'HOMEX'))
            ->to($toEmail)
            ->subject('✅ Xác nhận đặt lịch khám bệnh')
            ->html("
                    <div style='font-family: Arial, sans-serif;'>
                        <h2 style='color: #2c3e50;'>👋 Chào $patientName,</h2>
                        <p>📅 Bạn đã đặt lịch khám thành công với bác sĩ <strong>$doctorName</strong> vào ngày <strong>$appointmentDate</strong>.</p>
                        <p>💙 Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi.</p>
                        <hr>
                        <p style='font-size: 14px; color: #7f8c8d;'>Nếu có thắc mắc, vui lòng liên hệ 📞 <strong>Hotline: 1900 123 456</strong>.</p>
                    </div>
                ");

        $this->mailer->send($email);
    }
    public function sendAppointmentResult(
        string $toEmail,
        string $patientName,
        string $appointmentDate,
        string $doctorName,
        string $zipFilePath,
        string $zipFileName,
        string $password
    ) {
        $emailContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;'>
                <h2 style='color: #007bff; text-align: center;'>📄 Kết Quả Khám Bệnh</h2>
                <p>Xin chào <strong>$patientName</strong>,</p>
                <p>Bạn đã khám bệnh với bác sĩ <strong>$doctorName</strong> vào ngày <strong>$appointmentDate</strong>. Dưới đây là kết quả khám của bạn.</p>
                <hr style='border: 1px solid #ddd;'>
                <p><strong>📌 Hướng dẫn mở file:</strong></p>
                <ul>
                    <li>File đính kèm là một tập tin ZIP được bảo vệ bằng mật khẩu.</li>
                    <li><strong>Mật khẩu để giải nén:</strong> <span style='color: #d9534f; font-weight: bold;'>$password</span></li>
                    <li>Vui lòng sử dụng phần mềm giải nén như WinRAR hoặc 7-Zip để mở.</li>
                </ul>
                <p style='color: #555; font-size: 14px;'>Nếu có thắc mắc, vui lòng liên hệ với chúng tôi.</p>
                <hr style='border: 1px solid #ddd;'>
                <p style='text-align: center; font-size: 14px; color: #777;'>📧 HOMEX - Dịch vụ y tế chuyên nghiệp</p>
            </div>
        ";

        $email = (new Email())
            ->from(new Address('ntakhoa.work@gmail.com', 'HOMEX (thay mặt ' . $doctorName . ')'))
            ->to($toEmail)
            ->subject('📄 Kết quả khám bệnh - HOMEX')
            ->html($emailContent)
            ->attachFromPath($zipFilePath, $zipFileName, 'application/zip'); // Đính kèm file ZIP

        $this->mailer->send($email);
    }
}
