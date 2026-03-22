<?php

namespace App\Form;

use App\Entity\Staff;
use App\Entity\Enum\AccountStatus; // Ensure this path is correct
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class StaffType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // 1. Static Fields
        $builder
            ->add('firstName', TextType::class, ['label' => 'First Name'])
            ->add('lastName', TextType::class, ['label' => 'Last Name'])
            ->add('avatar', FileType::class, [
                'label' => 'Avatar',
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

        // 2. Conditional Password
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

        // 3. Dynamic Fields (Email, Status, isVerified) from the User Entity
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $staff = $event->getData();
            $form = $event->getForm();
            $user = ($staff && $staff->getUser()) ? $staff->getUser() : null;

            // Email
            $form->add('email', EmailType::class, [
                'mapped' => false,
                'data' => $user ? $user->getEmail() : null,
                'constraints' => [new NotBlank(['message' => 'Email is required'])],
            ]);

            // Status (Using your existing Enum)
            $form->add('status', EnumType::class, [
                'class' => AccountStatus::class,
                'mapped' => false,
                'data' => $user ? $user->getStatus() : AccountStatus::Active,
                'label' => 'Account Status',
            ]);

            // isVerified
            $form->add('isVerified', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'data' => $user ? $user->getIsVerified() : false,
                'label' => 'Verified Account',
                'attr' => ['class' => 'w-4 h-4 text-brand bg-gray-100 border-gray-300 rounded focus:ring-brand']
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Staff::class,
            'is_edit' => false,
        ]);
    }
}
