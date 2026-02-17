<?php

namespace App\Form;

use App\Entity\Staff;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType; // Added this
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
// Import Events
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class StaffType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // 1. Password (Only if NOT editing)
        if (!$options['is_edit']) {
            $builder->add('password', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Password cannot be blank.']),
                    new Length(['min' => 8, 'minMessage' => 'Min 8 characters.']),
                ],
            ]);
        }

        // 2. Email (Use Listener to pre-fill from User entity)
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $staff = $event->getData();
            $form = $event->getForm();

            // Check if staff exists and has a user
            $currentEmail = null;
            if ($staff && $staff->getUser()) {
                $currentEmail = $staff->getUser()->getEmail();
            }

            $form->add('email', EmailType::class, [
                'label' => 'Email',
                'mapped' => false,
                'data' => $currentEmail, // <--- Pre-fills the field
                'constraints' => [new NotBlank(['message' => 'Email cannot be blank.'])],
            ]);
        });

        $builder
            ->add('firstName', TextType::class, ['label' => 'First Name'])
            ->add('lastName', TextType::class, ['label' => 'Last Name'])
            ->add('avatar', FileType::class, [
                'label' => 'Avatar (JPEG or PNG file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Please upload a valid JPEG or PNG image.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Staff::class,
            'is_edit' => false,
        ]);
    }
}
