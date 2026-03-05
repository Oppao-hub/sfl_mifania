<?php

namespace App\Form;

use App\Entity\Enum\PaymentMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Complete Shipping Address',
                'constraints' => [new NotBlank(['message' => 'Please provide a shipping address.'])],
            ])
            ->add('city', TextType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('postalCode', TextType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'choices' => PaymentMethod::cases(),
                'choice_label' => fn (PaymentMethod $paymentMethod) => $paymentMethod->value,
                'expanded' => true, // Renders as radio buttons
                'multiple' => false,
                'label' => 'Payment Method',
            ])
            ->add('orderNotes', TextareaType::class, [
                'required' => false,
                'label' => 'Order Notes (Optional)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
