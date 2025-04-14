<?php

namespace App\Form;

use App\Entity\Specialty;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullname', TextType::class, [
                'label' => 'Họ tên',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])

            ->add('image', FileType::class, [
                'label' => 'Tải ảnh đại diện lên',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '2M', // Dung lượng tối đa 2MB
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Vui lòng upload file ảnh hợp lệ (JPEG hoặc PNG).',
                    ])
                ],
            ])

            ->add('email', TextType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])

            ->add('phoneNumber', TextType::class, [
                'label' => 'Số điện thoại',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])

            ->add('address', TextType::class, [
                'label' => 'Địa chỉ',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])

            ->add('gender', ChoiceType::class, [
                'label' => 'Giới tính',
                'required' => true,
                'choices' => [
                    'Nam' => 'Nam',
                    'Nữ' => 'Nữ',
                    'Khác' => 'Khác',
                ],
                'attr' => ['class' => 'form-control']
            ])

            ->add('dateOfBirth', DateType::class, [
                'label' => 'Ngày sinh',
                'widget' => 'single_text', // Hiển thị input dạng date
                'required' => true,
                'input' => 'datetime', // Chuyển đổi giá trị thành DateTime
                'attr' => ['class' => 'form-control dateOfYear-datepicker'],
                'format' => 'dd/MM/yyyy',
                'html5' => false,
            ])
        ;

        if ($options['is_doctor']) {
            $builder
                ->add('specialty', EntityType::class, [
                    'class' => Specialty::class,
                    'choice_label' => 'name',
                    'label' => 'Chuyên khoa',
                    'placeholder' => 'Chọn chuyên khoa',
                    'required' => true,
                    'attr' => ['class' => 'form-control'],
                    'constraints' => [
                        new Assert\NotBlank([
                            'message' => 'Vui lòng chọn chuyên khoa.',
                        ]),
                    ],
                ])

                ->add('bio', TextareaType::class, [
                    'label' => 'Giới thiệu',
                    'attr' => [
                        'class' => 'form-control',
                        'rows' => 5, // Số dòng hiển thị mặc định
                        'placeholder' => 'Nhập giới thiệu về bản thân...',
                    ],
                ])
                ->add('qualification', TextareaType::class, [
                    'label' => 'Thế mạnh chuyên môn',
                    'attr' => [
                        'class' => 'form-control',
                        'rows' => 5, // Số dòng hiển thị mặc định
                        'placeholder' => 'Nhập giới thiệu về bản thân...',
                    ],
                ])

                ->add('consultationFee', TextType::class, [
                    'label' => 'Phí khám',
                    'required' => true,
                    'attr' => ['class' => 'form-control', 'readonly' => false]
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_doctor' => false,
        ]);
    }
}
