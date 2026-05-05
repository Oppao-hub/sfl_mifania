<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\SubCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('slug', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Automatically generated if left blank'
                ]
            ])
            ->add('icon', TextType::class, [
                'required' => false,
                'label' => 'Icon Identifier',
                'attr' => [
                    'placeholder' => 'e.g., lucide:leaf, material-symbols:category'
                ],
                'help' => 'Use Iconify format (set:name). See ux-icons documentation.'
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a Parent Category...',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubCategory::class,
        ]);
    }
}
