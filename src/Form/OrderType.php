<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Order;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('orderDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Order Date',
            ])
            ->add('totalAmount', NumberType::class, [
                'label' => 'Total Amount',
                'html5' => true,
                'scale' => 2,
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method',
                'placeholder' => 'Select a payment method',
                'choices' => [
                    'Cash' => 'Cash',
                    'Credit Card' => 'Credit Card',
                    'Bank Transfer' => 'Bank Transfer',
                ],
                'data' => 'Cash'
            ])
            ->add('paymentStatus', ChoiceType::class, [
                'label' => 'Payment Status',
                'placeholder' => 'Select a payment status',
                'choices' => [
                    'Pending' => 'Pending',
                    'Paid' => 'Paid',
                    'Refunded' => 'Refunded',
                ],
                'data' => 'Pending'
            ])
            ->add('orderStatus', ChoiceType::class, [
                'label' => 'Order Status',
                'placeholder' => 'Select an order status',
                'choices' => [
                    'Completed' => 'Completed',
                    'Pending' => 'Pending',
                    'Processing' => 'Processing',
                    'Cancelled' => 'Cancelled',
                ],
                'data' => 'Pending'
            ])
            ->add('rewardPointsEarned', IntegerType::class, [
                'label' => 'Reward Points Earned',
                'data' => 0
            ])
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => 'id',
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
