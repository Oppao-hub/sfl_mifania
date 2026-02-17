<?php

namespace App\Form;

use App\DTO\CartItemDTO;
use App\Entity\Enum\Color;
use App\Entity\Enum\Size;
use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddToCartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // 1. Quantity Field: Used for the numeric input
        $builder->add('quantity', IntegerType::class, [
            'label' => false,
            'data' => 1,
            'attr' => [
                'min' => 1,
                'max' => $options['max_quantity'] ?? 10,
                'class' => 'w-12 text-center border-x border-gray-300 py-2 focus:outline-none',
                'readonly' => true,
            ],
        ]);

        // 2. Size Field: A hidden field whose value is set by JavaScript buttons
        $builder->add('size', EnumType::class, [
            'class' => Size::class,
            'label' => false,
            'required' => true,
            'multiple' => false,
            'expanded' => false,
            'choice_label' => fn(Size $size) => $size->value,
        ]);

        // 3. Color Field: Swapped to EnumType for validation and type-casting
        $builder->add('color', EnumType::class, [
            'class' => Color::class,        // Use the defined Color Enum
            'label' => false,
            'required' => true,             // Color selection is likely mandatory
            'expanded' => false,            // Renders as a single <select> dropdown
            'multiple' => false,
            'choice_value' => function (?Color $color): ?string {
                if ($color === null) {
                    return null;
                }
                // Return the Enum's backed string value
                return $color->value;
            },
        ]);

        // 4. Action Field: A hidden field to capture which button was clicked (Add to Cart or Buy Now)
        $builder->add('action', HiddenType::class, [
            'label' => false,
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // BIND THE FORM TO THE DTO
            'data_class' => CartItemDTO::class,
            'max_quantity' => 10,
            'attr' => ['novalidate' => 'novalidate'],
        ]);

        $resolver->setRequired('product');
        $resolver->setAllowedTypes('product', Product::class);
    }
}
