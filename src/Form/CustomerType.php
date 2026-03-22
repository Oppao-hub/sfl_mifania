<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Enum\AccountStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // 1. Identity Fields
        $builder
            ->add('firstName', TextType::class, ['label' => 'First Name'])
            ->add('lastName', TextType::class, ['label' => 'Last Name'])
            ->add('contactNumber', TextType::class, ['label' => 'Contact Number'])
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

        // 2. Logistics / Address
        $builder
            ->add('address', TextType::class, ['label' => 'Address'])
            ->add('city', TextType::class, ['required' => true])
            ->add('state', TextType::class, ['label' => 'State / Province', 'required' => true])
            ->add('country', TextType::class, ['required' => true])
            ->add('postalCode', TextType::class, ['required' => false]);

        // 3. Password (Only for New Records)
        if (!$options['is_edit']) {
            $builder->add('password', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Password required']),
                    new Length(['min' => 8]),
                ],
            ]);
        }

        // 4. Listener to handle User entity fields (Email, Status, Verified)
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $customer = $event->getData();
            $form = $event->getForm();
            $user = ($customer && $customer->getUser()) ? $customer->getUser() : null;

            $form->add('email', EmailType::class, [
                'mapped' => false,
                'data' => $user ? $user->getEmail() : null,
                'constraints' => [
                    new NotBlank(['message' => 'Email cannot be blank.']),
                    new Email(),
                ],
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
            'data_class' => Customer::class,
            'is_edit' => false,
        ]);
    }
}
