<?php

namespace App\DataFixtures;

use App\Entity\Specialty;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Admin
        $admin = new User();
        $admin->setEmail('sinoo@gmail.com');
        $admin->setFullname('Sinoo');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setImage('admin.jpg');
        $hashedPassword = $this->passwordHasher->hashPassword($admin, '123456');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // Doctor
        $doctor = new User();
        $doctor->setEmail('doe@gmail.com');
        $doctor->setFullname('Dr. John Doe');
        $doctor->setRoles(['ROLE_DOCTOR']);
        $doctor->setImage('doctor_doe_avt.jpg');
        $doctor->setDateOfBirth(new \DateTime('1980-01-01'));   
        $doctor->setGender('Male');
        $doctor->setQualification('BDS, MDS - Phẫu thuật Răng Hàm Mặt');
        $hashedPassword = $this->passwordHasher->hashPassword($doctor, '123456');
        $doctor->setPassword($hashedPassword);
        $doctor->setPhoneNumber('0123456789');
        $doctor->setAddress('An Phú, Ninh Kiều, Cần Thơ');
        $doctor->setBio('Bác sĩ Doe là chuyên gia hàng đầu trong lĩnh vực Nam khoa và Tim mạch, với nhiều năm kinh nghiệm trong chẩn đoán và điều trị các bệnh lý liên quan đến sức khỏe tim mạch và sinh lý nam.

            Với chuyên môn sâu rộng, bác sĩ đã giúp nhiều bệnh nhân cải thiện sức khỏe tim mạch, đồng thời tư vấn và điều trị các vấn đề như rối loạn cương dương, suy giảm sinh lý và các bệnh lý liên quan đến nội tiết tố nam.

            Bác sĩ Doe luôn đặt sức khỏe và sự hài lòng của bệnh nhân lên hàng đầu, áp dụng các phương pháp điều trị tiên tiến, kết hợp giữa y học hiện đại và tư vấn lối sống lành mạnh.

            🔹 Chuyên môn:
            ✔️ Điều trị bệnh lý tim mạch: cao huyết áp, bệnh mạch vành, suy tim
            ✔️ Rối loạn cương dương, xuất tinh sớm, suy giảm testosterone
            ✔️ Tư vấn và điều trị vô sinh nam, hiếm muộn
            ✔️ Kiểm tra sức khỏe tổng quát và phòng ngừa bệnh lý tim mạch

            Nếu bạn đang gặp vấn đề về sức khỏe nam giới hoặc tim mạch, đừng ngần ngại đặt lịch hẹn với bác sĩ [Tên Bác Sĩ] để được tư vấn và điều trị kịp thời!');
        $doctor->setConsultationFee(100000);
        // Lấy specialty từ reference
        $specialty = $this->getReference('specialty-cardiology', Specialty::class);
        $doctor->setSpecialty($specialty);
        $manager->persist($doctor);

        $this->addReference('doctor-doe', $doctor);

        // Patient
        $patient = new User();
        $patient->setEmail('khoasinno@gmail.com');
        $patient->setFullname('Mary');
        $patient->setRoles(['ROLE_PATIENT']);
        $patient->setImage('patient_mary.jpg');
        $patient->setPhoneNumber('0123456789');
        $patient->setAddress('An Phú, Ninh Kiều, Cần Thơ');
        $patient->setDateOfBirth(new \DateTime('1990-01-01'));
        $patient->setGender('Male');
        $hashedPassword = $this->passwordHasher->hashPassword($patient, '123456');
        $patient->setPassword($hashedPassword);
        $manager->persist($patient);
        $this->addReference('patient-mary', $patient);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SpecialtyFixtures::class,
        ];
    }
}
