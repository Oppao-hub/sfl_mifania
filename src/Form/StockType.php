<?php

namespace App\Form;

use App\Entity\Stock;
use App\Entity\product;
use App\Entity\User;
use App\Entity\Enum\StockStatus;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantity')
            ->add('location', ChoiceType::class, [
                'choices' => [
                    'Warehouse A' => 'Warehouse A',
                    'Warehouse B' => 'Warehouse B',
                    'Warehouse C' => 'Warehouse C',
                ]
            ])
            ->add('status', EnumType::class, [
                'class' => StockStatus::class, // <-- This line is essential
                'label' => 'Stock Status', // Added a label for clarity
                'placeholder' => 'Select a status', // Optional: Adds a default "Choose..." option
                'choice_label' => function (StockStatus $choice) {
                    // Optional: Make the display name more user-friendly
                    // e.g., converts 'OutOfStock' to 'Out of Stock'
                    return str_replace('_', ' ', $choice->value);
                },
            ])
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'multiple' => false,
                'placeholder' => 'Select a product'
            ])
            ->add('addedBy', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}
