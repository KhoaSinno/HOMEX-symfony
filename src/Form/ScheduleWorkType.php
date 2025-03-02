<?php

namespace App\Form;

use App\Entity\ScheduleWork;
use App\Entity\User;
use App\Enum\ScheduleStatus;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScheduleWorkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $schedule = $options['data'];
        $builder
            ->add('doctor', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where("u.roles LIKE :role") // Tìm kiếm các user có chứa ROLE_DOCTOR trong roles
                        ->setParameter('role', '%ROLE_DOCTOR%'); // Tìm chuỗi chứa ROLE_DOCTOR
                },
                'choice_label' => 'fullname',  // Hiển thị fullname của bác sĩ
                'label' => 'Chọn bác sĩ',
            ])
            ->add('date', null, [
                'widget' => 'single_text',
                'label' => 'Chọn ngày làm việc',
            ])
            ->add('maxPatient', null, [
                'label' => 'Số BN tối đa/ giờ khám',
            ])
            // ->add('timeSlots', ChoiceType::class, [
            //     'choices' => array_combine($options['time_slots'], $options['time_slots']),
            //     'expanded' => true,
            //     'multiple' => true,
            //     'label' => 'Chọn khung giờ làm việc',
            // ])
            ->add('timeSlots', ChoiceType::class, [
                'choices' => array_combine(array_map('strval', $options['time_slots']), array_map('strval', $options['time_slots'])),
                'expanded' => true, // Hiển thị dưới dạng checkbox
                'multiple' => true, // Cho phép chọn nhiều slot
                'required' => false,
                'mapped' => true, // Đảm bảo dữ liệu được bind vào entity
            ])



            ->add('status', HiddenType::class, [
                'data' => $schedule->getStatus()?->value ?? ScheduleStatus::AVAILABLE->value,
            ])
            ->add('slotDuration', ChoiceType::class, [
                'label' => 'Thời gian khám (phút)',
                'choices' => [
                    'Tất cả thời gian' => 'all',
                    '10 phút' => 10,
                    '20 phút' => 20,
                ],
                'attr' => ['class' => 'form-control', 'id' => 'slot-duration'],
                'required' => true,
                'mapped' => false, // Không map vào entity, khỏi cần chỉnh sửa chi
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ScheduleWork::class,
            'doctors' => [],
            'time_slots' => [],
        ]);

        // Khai báo option "doctors" và "time_slots"
        $resolver->setDefined(['doctors', 'time_slots']);
    }
}
