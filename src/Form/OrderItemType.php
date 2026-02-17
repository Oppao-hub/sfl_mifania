<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use BcMath\Number;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantity')
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'html5' => true,
                'scale' => 2,
                'attr' => [
                    'readonly' => true,
                ],
            ])
            ->add('subtotal', NumberType::class, [
                'label' => 'Subtotal',
                'html5' => true,
                'scale' => 2,
                'attr' => [
                    'readonly' => true,
                ],
            ])
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'choice_attr' => function($product) {
                return ['data-price' => $product->getPrice()];
                },
                'attr' => [
                    'min' => 1,
                    'class' => 'w-full p-2 border border-gray-300 rounded-xl text-center focus:ring-green-500 item-quantity',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}
