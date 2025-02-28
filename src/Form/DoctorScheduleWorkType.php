<?php

namespace App\Form;

use App\Entity\ScheduleWork;
use App\Entity\User;
use App\Enum\ScheduleStatus;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DoctorScheduleWorkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('maxPatient')
            ->add('date', null, [
                'widget' => 'single_text',
                'data' => new \DateTime(),
                'format' => 'dd-MM-yyyy',
            ])
            ->add('timeSlots', ChoiceType::class, [
                'choices' => array_unique(array_combine($options['time_slots'], $options['time_slots'])), // Loại bỏ trùng
                'expanded' => true,
                'multiple' => true,
                'label' => 'Chọn khung giờ làm việc',
            ])
            
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Available' => ScheduleStatus::AVAILABLE,
                    'Full' => ScheduleStatus::FULL,
                    'Canceled' => ScheduleStatus::CANCELED,
                ],
                'choice_label' => fn (ScheduleStatus $choice) => $choice->value, // Hiển thị tên Enum
                'choice_value' => fn (?ScheduleStatus $choice) => $choice?->value, // Lưu Enum dưới dạng string
                'expanded' => false,
                'multiple' => false,
                'label' => 'Trạng thái',
                'data' => ScheduleStatus::AVAILABLE, // Mặc định là Available
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ScheduleWork::class,
        ]);
        // Khai báo option "doctors" và "time_slots"
        $resolver->setDefined(['doctors', 'time_slots']);
    }
}
