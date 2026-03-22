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
use Symfony\Component\Validator\Constraints\Length; // <-- Added this use statement

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
                'constraints' => [new NotBlank(['message' => 'Please provide a city.'])],
            ])
            ->add('postalCode', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Please provide a postal code.']),
                    // 1. Added a Length constraint to enforce realistic postal codes
                    new Length([
                        'min' => 4,
                        'max' => 10,
                        'minMessage' => 'Your postal code must be at least {{ limit }} characters long.',
                        'maxMessage' => 'Your postal code cannot be longer than {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'choices' => PaymentMethod::cases(),
                'choice_label' => fn (PaymentMethod $paymentMethod) => $paymentMethod->value,
                'expanded' => true,
                'multiple' => false,
                'label' => 'Payment Method',
                // 2. Added NotBlank to ensure a radio button is actually selected
                'constraints' => [new NotBlank(['message' => 'Please select a payment method.'])],
            ])
            ->add('orderNotes', TextareaType::class, [
                'required' => false,
                'label' => 'Order Notes (Optional)',
                // 3. Added a Length constraint to prevent users from submitting massive amounts of text
                'constraints' => [
                    new Length([
                        'max' => 500,
                        'maxMessage' => 'Order notes cannot exceed {{ limit }} characters.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
