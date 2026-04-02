<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultClasses = 'w-full px-4 py-3 border border-gray-200 rounded-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-colors text-sm';

        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => $defaultClasses, 'placeholder' => 'Ex. Paolo Mifania'],
                'constraints' => [new NotBlank(['message' => 'Please enter your name.'])],
            ])
            ->add('email', EmailType::class, [
                'attr' => ['class' => $defaultClasses, 'placeholder' => 'example@gmail.com'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an email.']),
                    new Email(['message' => 'Please enter a valid email address.'])
                ],
            ])
            ->add('subject', TextType::class, [
                'attr' => ['class' => $defaultClasses, 'placeholder' => 'Subject'],
                'constraints' => [new NotBlank(['message' => 'Please enter a subject.'])],
            ])
            ->add('message', TextareaType::class, [
                'attr' => [
                    'class' => $defaultClasses . ' h-32 resize-none',
                    'placeholder' => 'Your message...',
                ],
                'constraints' => [new NotBlank(['message' => 'Please enter a message.'])],
            ])
        ;
    }
}
