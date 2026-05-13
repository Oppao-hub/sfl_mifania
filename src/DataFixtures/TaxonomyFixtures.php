<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\SubCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TaxonomyFixtures extends Fixture
{
    public const CAT_WOMEN = 'category_women';
    public const CAT_MEN = 'category_men';
    public const CAT_UNISEX = 'category_unisex';
    public const CAT_ACCESSORIES = 'category_accessories';

    public function load(ObjectManager $manager): void
    {
        // --- 1. MASTER CATEGORIES ---
        $women = new Category();
        $women->setName('Women');
        $women->setDescription('Elegantly designed apparel that celebrates both the wearer and the planet. Ethically produced to provide versatile, timeless fashion.');
        $manager->persist($women);
        $this->addReference(self::CAT_WOMEN, $women);

        $men = new Category();
        $men->setName('Men');
        $men->setDescription('Thoughtfully crafted essentials and tailored statement pieces. Built for durability and everyday comfort with an effortlessly sharp aesthetic.');
        $manager->persist($men);
        $this->addReference(self::CAT_MEN, $men);

        $unisex = new Category();
        $unisex->setName('Unisex');
        $unisex->setDescription('Fluid, versatile, and designed for everyone. Our unisex collection transcends traditional boundaries to offer relaxed, universally flattering silhouettes.');
        $manager->persist($unisex);
        $this->addReference(self::CAT_UNISEX, $unisex);

        $accessories = new Category();
        $accessories->setName('Accessories');
        $accessories->setDescription('Sustainable additions to complete your look. From recycled bags to ethically made jewelry.');
        $manager->persist($accessories);
        $this->addReference(self::CAT_ACCESSORIES, $accessories);

        // --- 2. SUB-CATEGORIES ---
        $subCategoriesData = [
            // Women Subcategories
            ['name' => 'Dresses & Jumpsuits', 'cat' => $women, 'desc' => 'Flowing, sustainable dresses and one-pieces.'],
            ['name' => 'Tops & Blouses', 'cat' => $women, 'desc' => 'Breathable organic cotton and linen tops.'],
            ['name' => 'Skirts & Trousers', 'cat' => $women, 'desc' => 'Tailored bottoms made from eco-friendly materials.'],

            // Men Subcategories
            ['name' => 'Shirts & Polos', 'cat' => $men, 'desc' => 'Classic button-downs and casual polos.'],
            ['name' => 'Trousers & Chinos', 'cat' => $men, 'desc' => 'Durable, ethically crafted legwear.'],

            // Unisex Subcategories
            ['name' => 'Hoodies & Sweaters', 'cat' => $unisex, 'desc' => 'Heavyweight, gender-neutral comfort wear.'],
            ['name' => 'Essential Tees', 'cat' => $unisex, 'desc' => 'The perfect everyday t-shirt, for everyone.'],
            
            // Accessories Subcategories
            ['name' => 'Bags & Totes', 'cat' => $accessories, 'desc' => 'Recycled canvas and sustainable carry-alls.'],
            ['name' => 'Jewelry', 'cat' => $accessories, 'desc' => 'Ethically sourced and handcrafted accessories.'],
        ];

        foreach ($subCategoriesData as $index => $data) {
            $subCat = new SubCategory();
            $subCat->setName($data['name']);
            $subCat->setCategory($data['cat']);
            $subCat->setDescription($data['desc']);

            $manager->persist($subCat);

            // We save a reference to each subcategory so the Product fixture can use them
            $this->addReference('subcat_' . $index, $subCat);
        }

        $manager->flush();
    }
}
