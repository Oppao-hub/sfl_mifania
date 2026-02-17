<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Order;
use App\Form\OrderItemType;
use App\Entity\Enum\PaymentMethod;
use App\Entity\Enum\PaymentStatus;
use App\Entity\Enum\OrderStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('totalAmount', NumberType::class, [
                'label' => 'Total Amount',
                'html5' => true,
                'scale' => 2,
                'attr' => [
                    'readonly' => true,
                    'placeholder' => 'Calculated automatically upon adding items',
                ],
            ])
            ->add('paymentMethod', EnumType::class, [
                'class' => PaymentMethod::class,
                'label' => 'Payment Method',
                'placeholder' => 'Select a payment method',
                'choice_label' => fn(PaymentMethod $method) => $method->value,
            ])
            ->add('paymentStatus', EnumType::class, [
                'class' => PaymentStatus::class,
                'label' => 'Payment Status',
                'placeholder' => 'Select a payment status',
                'choice_label' => fn(PaymentStatus $paymentStatus) => $paymentStatus->value,
            ])
            ->add('orderStatus', EnumType::class, [
                'class' => OrderStatus::class,
                'label' => 'Order Status',
                'placeholder' => 'Select an order status',
                'choice_label' => fn(OrderStatus $orderStatus) => $orderStatus->value,
            ])
            ->add('rewardPoints', IntegerType::class, [
                'label' => 'Reward Points',
                'attr' => [
                    'readonly' => true,
                    'placeholder' => 'Calculated automatically upon adding items',
                ],
            ])
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => fn($entity) => $entity->getFirstName() . ' ' . $entity->getLastName(),
            ])
            ->add('orderItems', CollectionType::class, [
                // Specifies the form type for each item in the collection
                'entry_type' => OrderItemType::class,
                // Enable adding new items via JavaScript
                'allow_add' => true,
                // Enable removing existing items
                'allow_delete' => true,
                // Important for persisting the collection relationship
                'by_reference' => false,
                'label' => false, // We'll handle the label in Twig
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
