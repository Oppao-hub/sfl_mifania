<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\SubCategory;
use App\Entity\Product;
use App\Entity\Enum\Size;
use App\Entity\Enum\Color;
use App\Entity\Enum\Gender;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Product Name'
            ])
            ->add('subCategory', EntityType::class, [
                'class' => SubCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a sub category',
                'label' => 'Sub Category'
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a category',
                'label' => 'Category'
            ])
            ->add('material', null, [
                'label' => 'Material'
            ])
            ->add('size', EnumType::class, [
                'class' => Size::class,
                'label' => 'Size',
                'placeholder' => 'Select a size',
                'choice_label' => fn(Size $size) => $size->value,
            ])
            ->add('color', EnumType::class, [
                'class' => Color::class,
                'label' => 'Color',
                'placeholder' => 'Select a color',
                'choice_label' => fn(Color $color) => $color->value,
            ])
            ->add('gender', EnumType::class, [
                'class' => Gender::class,
                'label' => 'Gender',
                'placeholder' => 'Select a gender',
                'choice_label' => fn(Gender $gender) => $gender->value,
            ])
            ->add('price', TextType::class, [
                'label' => 'Price (PHP)',
                'invalid_message' => 'Please enter a valid number for the price.',
            ])
            ->add('cost', TextType::class, [
                'label' => 'Cost (PHP)',
                'invalid_message' => 'Please enter a valid number for the cost.',
            ])
            ->add('description', null, [
                'label' => 'Description'
            ])
            ->add('ecoInfo', null, [
                'label' => 'Eco Information'
            ])
            ->add('image', FileType::class, [
                'label' => 'Product Image (JPEG or PNG file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPEG or PNG image.',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
