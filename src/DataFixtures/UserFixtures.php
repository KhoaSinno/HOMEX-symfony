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
        $admin->setDel(false);
        $admin->setEmail('sinoo@gmail.com');
        $admin->setFullname('Sinoo');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setImage('admin.jpg');
        $hashedPassword = $this->passwordHasher->hashPassword($admin, '123456');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // Doctor
        $doctor = new User();
        $doctor->setDel(false);
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
        $doctor->setBio('<p>⭐ <strong>B&aacute;c sĩ Doe</strong> - Chuy&ecirc;n gia h&agrave;ng đầu trong lĩnh vực <strong>Nam khoa</strong> v&agrave; <strong>Tim mạch</strong> ⭐<br />
        Với <strong>nhiều năm kinh nghiệm</strong>, b&aacute;c sĩ Doe l&agrave; người bạn đồng h&agrave;nh đ&aacute;ng tin cậy trong việc chẩn đo&aacute;n v&agrave; điều trị c&aacute;c bệnh l&yacute; về <strong>sức khỏe tim mạch</strong> v&agrave; <strong>sinh l&yacute; nam giới</strong>.</p>

        <p>💡 <strong>Chuy&ecirc;n m&ocirc;n s&acirc;u rộng</strong>:<br />
        B&aacute;c sĩ đ&atilde; hỗ trợ h&agrave;ng trăm bệnh nh&acirc;n cải thiện chất lượng cuộc sống nhờ &aacute;p dụng <strong>c&aacute;c phương ph&aacute;p điều trị ti&ecirc;n tiến</strong>, kết hợp giữa <strong>y học hiện đại</strong> v&agrave; <strong>tư vấn lối sống l&agrave;nh mạnh</strong>. Từ c&aacute;c vấn đề tim mạch đến rối loạn sinh l&yacute;, b&aacute;c sĩ lu&ocirc;n đặt <strong>sức khỏe</strong> v&agrave; <strong>sự h&agrave;i l&ograve;ng</strong> của bạn l&ecirc;n h&agrave;ng đầu.</p>

        <hr />
        <h3>🌟 C&aacute;c lĩnh vực chuy&ecirc;n m&ocirc;n</h3>

        <p>🔸 <strong>Bệnh l&yacute; tim mạch</strong>:<br />
        ✅ Cao huyết &aacute;p 💓<br />
        ✅ Bệnh mạch v&agrave;nh ❤️<br />
        ✅ Suy tim 🫁</p>

        <p>🔸 <strong>Sức khỏe nam giới</strong>:<br />
        ✅ Rối loạn cương dương 🚹<br />
        ✅ Xuất tinh sớm ⏰<br />
        ✅ Suy giảm testosterone 💉<br />
        ✅ V&ocirc; sinh nam &amp; hiếm muộn 👶</p>

        <p>🔸 <strong>Dịch vụ kh&aacute;c</strong>:<br />
        ✅ Kiểm tra sức khỏe tổng qu&aacute;t 🩺<br />
        ✅ Ph&ograve;ng ngừa bệnh l&yacute; tim mạch 🛡️</p>

        <hr />
        <h3>💪 Phương ch&acirc;m l&agrave;m việc</h3>

        <p>👉 <strong>&quot;Sức khỏe l&agrave; v&agrave;ng&quot;</strong> - B&aacute;c sĩ kh&ocirc;ng chỉ điều trị m&agrave; c&ograve;n gi&uacute;p bạn duy tr&igrave; <strong>lối sống l&agrave;nh mạnh</strong>.<br />
        👉 Sử dụng <strong>c&ocirc;ng nghệ y khoa ti&ecirc;n tiến</strong> 🔬 v&agrave; <strong>kinh nghiệm thực tiễn</strong> để mang lại kết quả tốt nhất.</p>

        <hr />
        <h3>📅 Đặt lịch ngay h&ocirc;m nay!</h3>

        <p>Nếu bạn đang gặp vấn đề về <strong>tim mạch</strong> hay <strong>sức khỏe nam giới</strong>, h&atilde;y li&ecirc;n hệ với <strong>B&aacute;c sĩ Doe</strong>!</p>');
        $doctor->setConsultationFee(100000);
        // Lấy specialty từ reference
        $specialty = $this->getReference('specialty-cardiology', Specialty::class);
        $doctor->setSpecialty($specialty);
        $manager->persist($doctor);

        $this->addReference('doctor-doe', $doctor);


        // Bác sĩ 1 - Nữ
        $doctor1 = new User();
        $doctor1->setDel(false);
        $doctor1->setEmail('anna.lee@gmail.com');
        $doctor1->setFullname('Dr. Anna Lee');
        $doctor1->setRoles(['ROLE_DOCTOR']);
        $doctor1->setImage('doctor_anna_lee.jpg');
        $doctor1->setDateOfBirth(new \DateTime('1985-06-15'));
        $doctor1->setGender('Female');
        $doctor1->setQualification('MBBS, MD - Nội khoa');
        $doctor1->setPassword($this->passwordHasher->hashPassword($doctor1, '123456'));
        $doctor1->setPhoneNumber('0987654321');
        $doctor1->setAddress('Quận 1, TP. Hồ Chí Minh');
        $doctor1->setConsultationFee(120000);
        $specialty = $this->getReference('specialty-neurology', Specialty::class);
        $doctor1->setSpecialty($specialty);
        $manager->persist($doctor1);
        $this->addReference('doctor-anna-lee', $doctor1);

        // Bác sĩ 2 - Nữ
        $doctor2 = new User();
        $doctor2->setDel(false);
        $doctor2->setEmail('emma.scott@gmail.com');
        $doctor2->setFullname('Dr. Emma Scott');
        $doctor2->setRoles(['ROLE_DOCTOR']);
        $doctor2->setImage('doctor_emma_scott.jpg');
        $doctor2->setDateOfBirth(new \DateTime('1987-09-22'));
        $doctor2->setGender('Female');
        $doctor2->setQualification('BDS, MDS - Nha khoa');
        $doctor2->setPassword($this->passwordHasher->hashPassword($doctor2, '123456'));
        $doctor2->setPhoneNumber('0976543210');
        $doctor2->setAddress('Hoàn Kiếm, Hà Nội');
        $doctor2->setConsultationFee(150000);
        $specialty = $this->getReference('specialty-dentist', Specialty::class);
        $doctor2->setSpecialty($specialty);
        $manager->persist($doctor2);
        $this->addReference('doctor-emma-scott', $doctor2);

        // Bác sĩ 3 - Nữ
        $doctor3 = new User();
        $doctor3->setDel(false);
        $doctor3->setEmail('lisa.brown@gmail.com');
        $doctor3->setFullname('Dr. Lisa Brown');
        $doctor3->setRoles(['ROLE_DOCTOR']);
        $doctor3->setImage('doctor_lisa_brown.jpg');
        $doctor3->setDateOfBirth(new \DateTime('1990-03-10'));
        $doctor3->setGender('Female');
        $doctor3->setQualification('MBBS, MS - Phẫu thuật Chỉnh hình');
        $doctor3->setPassword($this->passwordHasher->hashPassword($doctor3, '123456'));
        $doctor3->setPhoneNumber('0965432109');
        $doctor3->setAddress('Ngũ Hành Sơn, Đà Nẵng');
        $doctor3->setConsultationFee(180000);
        $specialty = $this->getReference('specialty-orthopedic', Specialty::class);
        $doctor3->setSpecialty($specialty);
        $manager->persist($doctor3);
        $this->addReference('doctor-lisa-brown', $doctor3);

        // Bác sĩ 4 - Nam
        $doctor4 = new User();
        $doctor4->setDel(false);
        $doctor4->setEmail('john.miller@gmail.com');
        $doctor4->setFullname('Dr. John Miller');
        $doctor4->setRoles(['ROLE_DOCTOR']);
        $doctor4->setImage('doctor_john_miller.jpg');
        $doctor4->setDateOfBirth(new \DateTime('1978-12-05'));
        $doctor4->setGender('Male');
        $doctor4->setQualification('MBBS, MD - Tim mạch');
        $doctor4->setPassword($this->passwordHasher->hashPassword($doctor4, '123456'));
        $doctor4->setPhoneNumber('0954321098');
        $doctor4->setAddress('Bình Thạnh, TP. Hồ Chí Minh');
        $doctor4->setConsultationFee(consultationFee: 200000);
        $specialty = $this->getReference('specialty-cardiology', Specialty::class);
        $doctor4->setSpecialty($specialty);
        $manager->persist($doctor4);
        $this->addReference('doctor-john-miller', $doctor4);

        // Bác sĩ 5 - Nam
        $doctor5 = new User();
        $doctor5->setDel(false);
        $doctor5->setEmail('david.clark@gmail.com');
        $doctor5->setFullname('Dr. David Clark');
        $doctor5->setRoles(['ROLE_DOCTOR']);
        $doctor5->setImage('doctor_david_clark.jpg');
        $doctor5->setDateOfBirth(new \DateTime('1982-07-30'));
        $doctor5->setGender('Male');
        $doctor5->setQualification('MBBS, MS - Tiết niệu');
        $doctor5->setPassword($this->passwordHasher->hashPassword($doctor5, '123456'));
        $doctor5->setPhoneNumber('0943210987');
        $doctor5->setAddress('Ba Đình, Hà Nội');
        $doctor5->setConsultationFee(170000);
        $specialty = $this->getReference('specialty-urology', Specialty::class);
        $doctor5->setSpecialty($specialty);
        $manager->persist($doctor5);
        $this->addReference('doctor-david-clark', $doctor5);

        // Patient
        $patient = new User();
        $patient->setDel(false);
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

        // Bệnh nhân 1 - Nam
        $patient1 = new User();
        $patient1->setDel(false);
        $patient1->setEmail('john.smith@gmail.com');
        $patient1->setFullname('John Smith');
        $patient1->setRoles(['ROLE_PATIENT']);
        $patient1->setImage('patient_john_smith.jpg');
        $patient1->setPhoneNumber('0987654321');
        $patient1->setAddress('Bình Thạnh, TP. Hồ Chí Minh');
        $patient1->setDateOfBirth(new \DateTime('1995-05-10'));
        $patient1->setGender('Male');
        $hashedPassword = $this->passwordHasher->hashPassword($patient1, '123456');
        $patient1->setPassword($hashedPassword);
        $manager->persist($patient1);
        $this->addReference('patient-john-smith', $patient1);

        // Bệnh nhân 2 - Nam
        $patient2 = new User();
        $patient2->setDel(false);
        $patient2->setEmail('david.nguyen@gmail.com');
        $patient2->setFullname('David Nguyen');
        $patient2->setRoles(['ROLE_PATIENT']);
        $patient2->setImage('patient_david_nguyen.jpg');
        $patient2->setPhoneNumber('0976543210');
        $patient2->setAddress('Hoàn Kiếm, Hà Nội');
        $patient2->setDateOfBirth(new \DateTime('1988-09-20'));
        $patient2->setGender('Male');
        $hashedPassword = $this->passwordHasher->hashPassword($patient2, '123456');
        $patient2->setPassword($hashedPassword);
        $manager->persist($patient2);
        $this->addReference('patient-david-nguyen', $patient2);


        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SpecialtyFixtures::class,
        ];
    }
}
