<?php

namespace App\DataFixtures;

use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ReviewFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Lấy bác sĩ Doe từ reference
        $doctorDoe = $this->getReference('doctor-doe', User::class);
        
        // Lấy danh sách bệnh nhân từ reference
        $patientMary = $this->getReference('patient-mary', User::class);
        $patientJohnSmith = $this->getReference('patient-john-smith', User::class);
        $patientDavidNguyen = $this->getReference('patient-david-nguyen', User::class);
        
        // Tạo review 1 - Đánh giá 5 sao từ Mary
        $review1 = new Review();
        $review1->setDoctor($doctorDoe);
        $review1->setPatient($patientMary);
        $review1->setRating(5);
        $review1->setComment('Bác sĩ Doe rất chuyên nghiệp và tận tâm. Tôi đã được tư vấn rất chi tiết về vấn đề tim mạch của mình. Cảm ơn bác sĩ rất nhiều!');
        $review1->setCreatedAt(new \DateTime('2023-12-15'));
        $manager->persist($review1);
        
        // Tạo review 2 - Đánh giá 4 sao từ John Smith
        $review2 = new Review();
        $review2->setDoctor($doctorDoe);
        $review2->setPatient($patientJohnSmith);
        $review2->setRating(4);
        $review2->setComment('Bác sĩ khám rất kỹ và tư vấn rõ ràng. Phòng khám sạch sẽ, nhân viên thân thiện. Tôi sẽ quay lại vào lần sau.');
        $review2->setCreatedAt(new \DateTime('2024-01-20'));
        $manager->persist($review2);
        
        // Tạo review 3 - Đánh giá 5 sao từ David Nguyen
        $review3 = new Review();
        $review3->setDoctor($doctorDoe);
        $review3->setPatient($patientDavidNguyen);
        $review3->setRating(5);
        $review3->setComment('Tôi đã tìm kiếm một bác sĩ tim mạch giỏi trong thời gian dài và rất vui khi đã tìm thấy BS Doe. Bác sĩ rất am hiểu và tận tâm giải thích mọi thắc mắc của tôi. Kết quả điều trị rất tốt.');
        $review3->setCreatedAt(new \DateTime('2024-02-05'));
        $manager->persist($review3);
        
        // Tạo review 4 - Đánh giá 3 sao (trung bình) để đa dạng đánh giá
        $review4 = new Review();
        $review4->setDoctor($doctorDoe);
        $review4->setPatient($patientMary); // Mary đánh giá thêm lần nữa
        $review4->setRating(3);
        $review4->setComment('Lần khám thứ hai của tôi không tốt như lần đầu. Thời gian chờ đợi khá lâu và bác sĩ có vẻ vội vàng. Tuy nhiên, chuyên môn vẫn tốt.');
        $review4->setCreatedAt(new \DateTime('2024-03-10'));
        $manager->persist($review4);
        
        // Tạo thêm một số đánh giá cho các bác sĩ khác nếu muốn
        // ...
        
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}