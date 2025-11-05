<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\QRTag;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a category',
                'label' => 'Category'
            ])
            ->add('qrTag', EntityType::class, [
                'class' => QRTag::class,
                'choice_label' => 'qrCodeValue',
                'placeholder' => 'Select a QR Tag',
                'label' => 'QR Tag'
            ])
            ->add('material', null, [
                'label' => 'Material'
            ])
            ->add('size', ChoiceType::class, [
                'label' => 'Size',
                'choices' => [
                    'N\A' => 'N\A',
                    'Small' => 'Small',
                    'Medium' => 'Medium',
                    'Large' => 'Large',
                    'X-Large' => 'X-Large',
                    'XX-Large' => 'XX-Large'
                ],
            ])
            ->add('color', null, [
                'label' => 'Color'
            ])
            ->add('price', TextType::class, [
                'label' => 'Price (PHP)',
                'invalid_message' => 'Please enter a valid number for the price.',
            ])
            ->add('cost', TextType::class, [
                'label' => 'Cost (PHP)',
                'invalid_message' => 'Please enter a valid number for the cost.',
            ])
            ->add('points', TextType::class, [
                'label' => 'Points',
                'invalid_message' => 'Please enter a valid number for the points.',
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
