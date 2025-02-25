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
}
