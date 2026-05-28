<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class WalletTransferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recipientEmail', EmailType::class, [
                'label' => 'Recipient email',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter the recipient email.']),
                    new Assert\Email(['message' => 'Please enter a valid email address.']),
                ],
                'attr' => ['placeholder' => 'friend@example.com'],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Amount (PHP)',
                'html5' => true,
                'scale' => 2,
                'attr' => ['min' => 0.01, 'step' => 0.01, 'placeholder' => '0.00'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter an amount.']),
                    new Assert\Positive(['message' => 'Amount must be greater than zero.']),
                ],
            ])
            ->add('note', TextType::class, [
                'label' => 'Note (optional)',
                'required' => false,
                'attr' => ['placeholder' => 'e.g. Shared lunch fund'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'wallet_transfer',
        ]);
    }
}
