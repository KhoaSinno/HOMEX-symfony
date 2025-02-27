<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DoctorAppointmentType extends AbstractType
{
    private $userRepo;
    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('appointmentDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Ngày hẹn',
                'attr' => ['class' => 'form-control'],
                'format' => 'dd/MM/yyyy',
                'html5' => false,
            ])
            ->add('appointmentTime', null, [
                'label' => 'Giờ hẹn',
                'attr' => [
                    'class' => 'form-control',
                    // 'readonly' => true
                ]
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Ghi chú',
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('reason', TextareaType::class, [
                'label' => 'Lý do',
                'attr' => ['class' => 'form-control', 'rows' => 2]
            ])
            // ->add('status', ChoiceType::class, [
            //     'label' => 'Trạng thái',
            //     'choices' => [
            //         'Chờ xác nhận' => 'pending',
            //         'Đã xác nhận' => 'confirmed',
            //         'Hủy bỏ' => 'cancelled'
            //     ],
            //     'attr' => ['class' => 'form-control']
            // ])
            ->add('price', TextType::class, [
                'label' => 'Giá tiền',
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => true
                ]
            ])
            ->add('result', TextareaType::class, [
                'label' => 'Mô tả kết quả',
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('forWho', ChoiceType::class, [
                'label' => 'Dành cho',
                'choices' => [
                    'Bản thân' => 'self',
                    'Người thân' => 'family',
                    'Bạn bè' => 'friend',
                    'Khác' => 'other'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('patientFullname', null, [
                'label' => 'Họ tên bệnh nhân',
                'attr' => ['class' => 'form-control']
            ])
            ->add('patientDateOfBirth', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Ngày sinh bệnh nhân',
                'attr' => ['class' => 'form-control'],
                'format' => 'dd/MM/yyyy',
                'html5' => false,
            ])
            ->add('patientPhoneNumber', null, [
                'label' => 'Số điện thoại bệnh nhân',
                'attr' => ['class' => 'form-control']
            ])
            ->add('patientAddress', null, [
                'label' => 'Địa chỉ bệnh nhân',
                'attr' => ['class' => 'form-control']
            ])
            ->add('patientGender', ChoiceType::class, [
                'label' => 'Giới tính bệnh nhân',
                'choices' => [
                    'Nam' => 'male',
                    'Nữ' => 'female',
                    'Khác' => 'other'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('patientEmail', null, [
                'label' => 'Email bệnh nhân',
                'attr' => ['class' => 'form-control']
            ])
            ->add('resultFile', FileType::class, [
                'label' => 'Tải file kết quả (PDF/Hình ảnh)',
                'mapped' => false, // Không ánh xạ vào entity
                'required' => true, // Bắt buộc tải lên
                'attr' => ['class' => 'form-control'],
            ])

            // ->add('paymentStatus', ChoiceType::class, [
            //     'label' => 'Trạng thái thanh toán',
            //     'choices' => [
            //         'Chưa thanh toán' => 'unpaid',
            //         'Đã thanh toán' => 'paid'
            //     ],
            //     'attr' => ['class' => 'form-control']
            // ])
            // ->add('patient', EntityType::class, [
            //     'class' => User::class,
            //     'choice_label' => 'fullname',
            //     'label' => 'Bệnh nhân',
            //     'choices'=> $this->userRepo->findUserByRole('ROLE_PATIENT'), 
            //     'attr' => ['class' => 'form-control']
            // ])
            // ->add('doctor', EntityType::class, [
            //     'class' => User::class,
            //     'choice_label' => 'fullname',
            //     'label' => 'Bác sĩ phụ trách',
            //     'choices'=> $this->userRepo->findUserByRole('ROLE_DOCTOR'), 
            //     'attr' => ['class' => 'form-control']
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
        ]);
    }
}
