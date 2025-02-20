<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('oldPassword', PasswordType::class, [
                'label' => 'Mật khẩu hiện tại',
                'mapped' => false, // Quan trọng! Vì oldPassword không thuộc entity User
                'constraints' => [
                    new NotBlank(['message' => 'Vui lòng nhập mật khẩu hiện tại.'])
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => ['label' => 'Mật khẩu mới'],
                'second_options' => ['label' => 'Xác nhận mật khẩu mới'],
                'mapped' => false, // Quan trọng! Vì newPassword không thuộc entity User
                'constraints' => [
                    new NotBlank(['message' => 'Vui lòng nhập mật khẩu mới']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
