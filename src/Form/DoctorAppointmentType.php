<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DoctorAppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('appointmentDate', null, [
                'widget' => 'single_text',
            ])
            ->add('appointmentTime')
            ->add('note')
            ->add('status')
            ->add('price')
            ->add('result')
            ->add('forWho')
            ->add('reason')
            ->add('patientFullname')
            ->add('patientDateOfBirth', null, [
                'widget' => 'single_text',
            ])
            ->add('patientPhoneNumber')
            ->add('patientAddress')
            ->add('patientGender')
            ->add('patientEmail')
            ->add('paymentStatus')
            ->add('invoiceNumber')
            ->add('patient', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            ->add('doctor', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
        ]);
    }
}
