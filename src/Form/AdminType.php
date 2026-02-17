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

class AdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if(!$options['is_edit']){
            $builder
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Password cannot be blank.']),
                    new Length(['min' => 8, 'minMessage' => 'Password must be at least 8 characters.',
                    'max' => 255, 'maxMessage' => 'Password cannot be longer than {{ limit }} characters.'
                    ]),
                ],
            ]);
        }

        $builder
            ->add('email', EmailType::class, [
            'label' => 'Email',
            'mapped' => false,
            'constraints' => [
                new NotBlank(['message' => 'Email cannot be blank.']),
                // ADD THIS:
                new Email([
                    'message' => 'The email "{{ value }}" is not a valid email.',
                    'mode' => 'strict',
                    ]),
            ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First Name',

            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
            ])
            ->add('avatar', FileType::class, [
                'label' => 'Avatar (JPEG or PNG file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPEG or PNG image.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Admin::class,
            'is_edit' => false,
        ]);
    }
}
