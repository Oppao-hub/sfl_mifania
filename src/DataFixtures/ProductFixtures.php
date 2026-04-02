<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\SubCategory; // <-- ADDED IMPORT
use App\Entity\Story;       // <-- ADDED IMPORT
use App\Entity\Enum\Color;
use App\Entity\Enum\Size;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Ensure you have an array of sizes and colors to randomize
        $sizes = Size::cases();
        $colors = Color::cases();

        $productsData = [
            [
                'name' => 'Organic Bamboo Wrap Dress',
                'material' => '100% Organic Bamboo',
                'price' => '3200.00',
                'cost' => '1100.00',
                'desc' => 'A versatile, universally flattering wrap dress made from incredibly soft bamboo fiber.',
                'eco' => 'Saves 40L of water compared to standard cotton.',
                'subcat_ref' => 'subcat_0', // Dresses
                'story_ref' => StoryFixtures::STORY_BAMBOO
            ],
            [
                'name' => 'Hemp Linen Button-Down',
                'material' => 'Hemp & Organic Linen Blend',
                'price' => '2400.00',
                'cost' => '900.00',
                'desc' => 'A structured yet breathable shirt perfect for both the office and the weekend.',
                'eco' => 'Hemp actively absorbs CO2 during growth.',
                'subcat_ref' => 'subcat_3', // Men's Shirts
                'story_ref' => StoryFixtures::STORY_HEMP
            ],
            [
                'name' => 'Recycled PET Tote Bag',
                'material' => 'Post-Consumer Recycled Plastics',
                'price' => '1200.00',
                'cost' => '400.00',
                'desc' => 'A highly durable, water-resistant everyday tote built from 12 recycled plastic bottles.',
                'eco' => 'Prevents 12 plastic bottles from reaching the ocean.',
                'subcat_ref' => 'subcat_7', // Bags
                'story_ref' => StoryFixtures::STORY_EVERYDAY
            ],
            [
                'name' => 'Gender-Neutral Organic Hoodie',
                'material' => 'GOTS Certified Organic Cotton',
                'price' => '2800.00',
                'cost' => '1200.00',
                'desc' => 'The ultimate heavyweight comfort hoodie, designed with a relaxed, inclusive fit.',
                'eco' => 'Zero pesticides used in the cotton farming process.',
                'subcat_ref' => 'subcat_5', // Hoodies
                'story_ref' => StoryFixtures::STORY_COTTON
            ],
            [
                'name' => 'Minimalist Tencel Trousers',
                'material' => '100% Tencel Lyocell',
                'price' => '3500.00',
                'cost' => '1400.00',
                'desc' => 'Draping beautifully and resisting wrinkles, these trousers are a staple of sustainable elegance.',
                'eco' => 'Produced in a closed-loop system recycling 99% of solvents.',
                'subcat_ref' => 'subcat_2', // Women's Trousers
                'story_ref' => StoryFixtures::STORY_MINIMALIST
            ],
        ];

        foreach ($productsData as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setMaterial($data['material']);
            $product->setPrice($data['price']);
            $product->setCost($data['cost']);
            $product->setDescription($data['desc']);
            $product->setEcoInfo($data['eco']);

            // Randomize enums
            $product->setSize($sizes[array_rand($sizes)]);
            $product->setColor($colors[array_rand($colors)]);

            // FIX: Added the specific Entity::class as the required second argument!
            $product->setSubCategory($this->getReference($data['subcat_ref'], SubCategory::class));
            $product->setStory($this->getReference($data['story_ref'], Story::class));

            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            StoryFixtures::class,
            TaxonomyFixtures::class,
        ];
    }
}
