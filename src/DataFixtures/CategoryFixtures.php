<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const CLOTHING_CATEGORY_REFERENCE = 'category-clothing';
    public const DRESSES_CATEGORY_REFERENCE = 'category-dresses';
    public const SHOES_CATEGORY_REFERENCE = 'category-shoes';
    public const ACCESSORIES_CATEGORY_REFERENCE = 'category-accessories';
    public function load(ObjectManager $manager): void
    {
        $categories = [
            ['Clothing', 'Sustainable wardrobe staples that blend comfort, quality, and eco-conscious materials. Designed to be versatile, stylish, and planet-friendly.'],
            ['Dresses', 'A curated collection of ethically made dresses designed for comfort, confidence, and conscious living. Every piece is crafted from eco-friendly fabrics and made to last.'],
            ['Shoes', 'Eco-friendly footwear that unites sustainable craftsmanship with timeless design. Each pair is made using recycled, vegan, or natural materials.'],
            ['Accessories', 'Thoughtfully crafted accessories that complete your sustainable look. Each piece tells a story of ethical craftsmanship and circular design.'],
        ];

        foreach ($categories as [$name, $description]) {
            $category = new Category();
            $category->setName($name);
            $category->setDescription($description);
            $category->setCreatedAt(new \DateTimeImmutable());
            $category->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($category);

            switch ($name) {
                case 'Clothing':
                    $this->addReference(self::CLOTHING_CATEGORY_REFERENCE, $category);
                    break;
                case 'Dresses':
                    $this->addReference(self::DRESSES_CATEGORY_REFERENCE, $category);
                    break;
                case 'Shoes':
                    $this->addReference(self::SHOES_CATEGORY_REFERENCE, $category);
                    break;
                case 'Accessories':
                    $this->addReference(self::ACCESSORIES_CATEGORY_REFERENCE, $category);
                    break;
            }
        }

        $manager->flush();
    }
}
