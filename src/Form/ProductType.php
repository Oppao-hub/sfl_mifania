<?php

namespace App\Form;

use App\Entity\SubCategory;
use App\Entity\Product;
use App\Entity\Story;
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
            ->add('name', TextType::class, [
                'label' => 'Product Name'
            ])
            // DELETED Category field to enforce database normalization.
            ->add('subCategory', EntityType::class, [
                'class' => SubCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a sub-category',
                'label' => 'Sub Category'
            ])
            ->add('material', TextType::class, [
                'label' => 'Primary Material'
            ])
            ->add('size', EnumType::class, [
                'class' => Size::class,
                'label' => 'Size',
                'placeholder' => 'Select a size',
                'choice_label' => fn(Size $size) => $size->value,
            ])
            ->add('color', EnumType::class, [
                'class' => Color::class,
                'label' => 'Color Palette',
                'placeholder' => 'Select a color',
                'choice_label' => fn(Color $color) => $color->value,
            ])
            ->add('gender', EnumType::class, [
                'class' => Gender::class,
                'label' => 'Gender / Fit',
                'placeholder' => 'Select a gender',
                'choice_label' => fn(Gender $gender) => $gender->value,
            ])
            ->add('price', TextType::class, [
                'label' => 'MSRP (PHP)',
                'invalid_message' => 'Please enter a valid number for the price.',
            ])
            ->add('cost', TextType::class, [
                'label' => 'Archival Cost (PHP)',
                'invalid_message' => 'Please enter a valid number for the cost.',
            ])
            ->add('description', null, [
                'label' => 'Narrative Description'
            ])
            // REPURPOSED ecoInfo to be strictly for short stats
            ->add('ecoInfo', TextType::class, [
                'label' => 'Quick Impact Stats',
                'required' => false,
                'attr' => [
                    'placeholder' => 'e.g., Saves 70L Water, 100% Fair Trade'
                ]
            ])
            ->add('story', EntityType::class, [
                'class' => Story::class,
                'choice_label' => 'title',
                'placeholder' => 'Use Default Mifania Manifesto',
                'required' => false,
                'label' => 'Transparency Narrative'
            ])
            ->add('image', FileType::class, [
                'label' => 'Product Visual (JPEG or PNG)',
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
