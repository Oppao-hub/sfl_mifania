<?php

namespace App\Form;

use App\Entity\Stock;
use App\Entity\Product;
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
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'multiple' => false,
                'placeholder' => 'Select a product'
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
