<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullname', TextType::class, [
                'label' => 'Tên bác sĩ',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])

            ->add('image', FileType::class, [
                'label' => 'Hình ảnh',
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
            // ->add('roles', ChoiceType::class, [
            //     'choices' => [
            //         'Admin' => 'ROLE_ADMIN',
            //         'Doctor' => 'ROLE_DOCTOR',
            //         'Patient' => 'ROLE_PATIENT',
            //     ],
            //     'multiple' => true, // Cho phép chọn nhiều giá trị
            //     'expanded' => true, // Sử dụng checkbox hoặc radio button
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
