<?php

namespace App\Form;

use App\Entity\ScheduleWork;
use App\Enum\ScheduleStatus;
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
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control schedule-datepicker'],
                'required' => true,
                'input' => 'datetime', // Chuyển đổi giá trị thành DateTime
                'format' => 'dd-MM-yyyy', // tau định nghĩa format ngày tháng năm, thì tất cả nơi khi ref thì phải đúng format này
                'html5' => false, // Dùng thư viện Datepicker nên đéo thể nào bật date engine của web đc
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
