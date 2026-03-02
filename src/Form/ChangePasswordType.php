<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Current password',
                'mapped' => false,
                'attr' => ['autocomplete' => 'current-password', 'placeholder' => 'Enter your current password'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your current password.'),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'attr' => ['autocomplete' => 'current-password', 'placeholder' => 'Enter your current password'],
                'first_options' => ['label' => 'New password', 'attr' => ['autocomplete' => 'new-password', 'placeholder' => 'Enter your new password']],
                'second_options' => ['label' => 'Confirm new password', 'attr' => ['autocomplete' => 'confirm-password', 'placeholder' => 'Enter your new password again']],
                'invalid_message' => 'The new password fields must match.',
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter a new password.'),
                    new Assert\Length(min: 8, minMessage: 'Password must be at least {{ limit }} characters.'),
                ],
            ]);
    }
}
