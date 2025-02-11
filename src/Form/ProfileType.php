<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\File;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullname', TextType::class, ['label' => 'Họ tên'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('phoneNumber', TextType::class, ['label' => 'Số điện thoại'])
            ->add('address', TextType::class, ['label' => 'Địa chỉ'])
            ->add('image', FileType::class, [
                'label' => 'Ảnh đại diện',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '2M', 
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Vui lòng upload file ảnh hợp lệ (JPEG hoặc PNG).',
                    ])
                ],
            ]);

        // Nếu user là Doctor thì thêm các trường bio và consultationFee
        if ($options['is_doctor']) {
            $builder
                ->add('consultationFee', TextType::class, ['label' => 'Phí khám'])
                ->add('bio', TextareaType::class, [
                    'label' => 'Giới thiệu',
                    'attr' => [
                        'class' => 'form-control',
                        'rows' => 5, // Số dòng hiển thị mặc định
                        'placeholder' => 'Nhập giới thiệu về bản thân...',
                    ],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_doctor' => false, // Mặc định không phải Doctor
        ]);
    }
}
