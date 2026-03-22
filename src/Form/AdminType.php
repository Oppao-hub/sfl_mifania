<?php

namespace App\Form;

use App\Entity\Admin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Entity\Enum\AccountStatus;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['label' => 'First Name'])
            ->add('lastName', TextType::class, ['label' => 'Last Name'])
            ->add('avatar', FileType::class, [
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

        if (!$options['is_edit']) {
            $builder->add('password', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Password required']),
                    new Length(['min' => 8]),
                ],
            ]);
        }

        // Listener to handle the User entity fields
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $admin = $event->getData();
            $form = $event->getForm();
            $user = ($admin && $admin->getUser()) ? $admin->getUser() : null;

            $form->add('email', EmailType::class, [
                'mapped' => false,
                'data' => $user ? $user->getEmail() : null,
                'constraints' => [new NotBlank(), new Email()],
            ]);

            $form->add('status', EnumType::class, [
                'class' => AccountStatus::class,
                'mapped' => false,
                'data' => $user ? $user->getStatus() : AccountStatus::Active,
            ]);

            $form->add('isVerified', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'data' => $user ? $user->getIsVerified() : false,
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Admin::class,
            'is_edit' => false,
        ]);
    }
}
