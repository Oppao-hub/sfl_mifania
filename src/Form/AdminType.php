<?php

namespace App\Form;

use App\Entity\Admin;
use App\Entity\Enum\AccountStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminType extends AbstractType
{
    public function __construct(private Security $security)
    {
    }

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
                    ])
                ],
            ]);

        if (!$options['is_edit']) {
            $builder->add('password', PasswordType::class, [
                'mapped' => false,
            ]);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $admin = $event->getData();
            $form = $event->getForm();
            $user = ($admin && $admin->getUser()) ? $admin->getUser() : null;

            $form->add('email', EmailType::class, [
                'mapped' => false,
                'data' => $user ? $user->getEmail() : null,
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

            // --- THE PRIVILEGE ESCALATION GUARDRAIL ---

            //Everyone creating an admin can assign the standard ROLE_ADMIN
            $roleChoices = [
                'Standard Administrator' => 'ROLE_ADMIN',
            ];

            //ONLY show the Super Admin checkbox if the person viewing the form is a Super Admin!
            if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
                $roleChoices['System Super Admin (Master Access)'] = 'ROLE_SUPER_ADMIN';
            }

            $form->add('roles', ChoiceType::class, [
                'mapped' => false,
                'choices' => $roleChoices,
                'multiple' => true,
                'expanded' => true, // This forces Symfony to render Checkboxes instead of a select dropdown!
                'data' => $user ? $user->getRoles() : ['ROLE_ADMIN'], // Default to standard admin
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
