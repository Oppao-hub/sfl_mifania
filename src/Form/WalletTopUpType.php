<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class WalletTopUpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
            ->add('description', TextType::class, [
                'label' => 'Note (optional)',
                'required' => false,
                'attr' => ['placeholder' => 'e.g. Loaded via GCash'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'wallet_top_up',
        ]);
    }
}
