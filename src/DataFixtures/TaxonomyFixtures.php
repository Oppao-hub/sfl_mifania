<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\SubCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TaxonomyFixtures extends Fixture
{
    public const CAT_WOMENS = 'category_womens';
    public const CAT_MENS = 'category_mens';
    public const CAT_UNISEX = 'category_unisex';

    public function load(ObjectManager $manager): void
    {
        // --- 1. MASTER CATEGORIES ---
        $womenswear = new Category();
        $womenswear->setName('Womenswear');
        $womenswear->setDescription('Elegantly designed apparel that celebrates both the wearer and the planet. Ethically produced to provide versatile, timeless fashion.');
        $manager->persist($womenswear);
        $this->addReference(self::CAT_WOMENS, $womenswear);

        $menswear = new Category();
        $menswear->setName('Menswear');
        $menswear->setDescription('Thoughtfully crafted essentials and tailored statement pieces. Built for durability and everyday comfort with an effortlessly sharp aesthetic.');
        $manager->persist($menswear);
        $this->addReference(self::CAT_MENS, $menswear);

        $unisex = new Category();
        $unisex->setName('Unisex & Neutral');
        $unisex->setDescription('Fluid, versatile, and designed for everyone. Our unisex collection transcends traditional boundaries to offer relaxed, universally flattering silhouettes.');
        $manager->persist($unisex);
        $this->addReference(self::CAT_UNISEX, $unisex);

        // --- 2. SUB-CATEGORIES ---
        $subCategoriesData = [
            // Womenswear Subcategories
            ['name' => 'Dresses & Jumpsuits', 'cat' => $womenswear, 'desc' => 'Flowing, sustainable dresses and one-pieces.'],
            ['name' => 'Tops & Blouses', 'cat' => $womenswear, 'desc' => 'Breathable organic cotton and linen tops.'],
            ['name' => 'Skirts & Trousers', 'cat' => $womenswear, 'desc' => 'Tailored bottoms made from eco-friendly materials.'],

            // Menswear Subcategories
            ['name' => 'Shirts & Polos', 'cat' => $menswear, 'desc' => 'Classic button-downs and casual polos.'],
            ['name' => 'Trousers & Chinos', 'cat' => $menswear, 'desc' => 'Durable, ethically crafted legwear.'],

            // Unisex Subcategories
            ['name' => 'Hoodies & Sweaters', 'cat' => $unisex, 'desc' => 'Heavyweight, gender-neutral comfort wear.'],
            ['name' => 'Essential Tees', 'cat' => $unisex, 'desc' => 'The perfect everyday t-shirt, for everyone.'],
            ['name' => 'Bags & Totes', 'cat' => $unisex, 'desc' => 'Recycled canvas and sustainable carry-alls.'],
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
