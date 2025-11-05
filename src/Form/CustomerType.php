<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Wallet;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use App\Entity\Enum\AccountStatus;
use App\Entity\Enum\VerificationStatus;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType; // Added for 'bio'
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // --- General Details Section ---
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First name',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name',
                'required' => true,
            ])
            ->add('dateOfBirth', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime_immutable',
                'label' => 'Date of Birth',
            ])
            ->add('contactNumber', TextType::class, [
                'label' => 'Contact Number',
                'required' => true,
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
        ;

        // --- Address and Location Details Section ---
        $builder
            ->add('address', TextType::class, [
                'label' => 'Street Address / Line 1',
                'required' => true,
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
                'required' => false, // Entity allows null
            ])
            ->add('bio', TextareaType::class, [ // Changed to TextareaType for a larger input field
                'label' => 'Bio / About the Customer',
                'required' => false, // Entity allows null
            ])
        ;

        // --- Admin Status Fields ---
        if ($options['is_admin'] === true) {
            $builder
                ->add('accountStatus', EnumType::class, [
                    'class' => AccountStatus::class,
                    'choice_label' => fn(AccountStatus $status) => $status->name,
                    'label' => 'Account Status',
                ])
                ->add('verificationStatus', EnumType::class, [
                    'class' => VerificationStatus::class,
                    'choice_label' => fn(VerificationStatus $status) => $status->name,
                    'label' => 'Verification Status',
                ])
            ;
        }

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
            'is_admin' => false,
        ]);
    }
}
