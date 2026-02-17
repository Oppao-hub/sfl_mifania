<?php

namespace App\Form;

use App\Entity\Customer;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if(!$options['is_edit']){
            $builder
                ->add('email', EmailType::class, [
                'label' => 'Email',
                'mapped' => false,
                'constraints' => [new NotBlank(['message' => 'Email cannot be blank.'])],
            ])
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
            ->add('firstName', TextType::class, [
                'label' => 'First name',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name',
            ])
            ->add('contactNumber', TextType::class, [
                'label' => 'Contact Number',
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
                    ])
                ]
            ])
            ->add('address', TextType::class, [
                'label' => 'Address',
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
                'required' => true,
            ])
            ->add('state', TextType::class, [
                'label' => 'State / Province',
                'required' => true,
            ])
            ->add('country', TextType::class, [
                'label' => 'Country',
                'required' => true,
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Postal Code',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
            'is_edit' => false,
        ]);
    }
}
