<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Enum\Size;
use App\Entity\SubCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\DataFixtures\SubCategoryFixtures;
use App\Entity\Enum\Color;

class DressFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get the subcategory reference for Casual Dresses
        /** @var SubCategory $subCategory */
        $subCategory = $this->getReference(SubCategoryFixtures::CASUAL_DRESSES_REFERENCE, SubCategory::class);

        $productsData = [
            // --- 10 Dress Products ---
            [
                'name' => 'Organic Cotton Day Dress',
                'material' => 'Organic Cotton',
                'size' => 'Medium',
                'color' => Color::BLUE,
                'price' => '1299.00',
                'cost' => '550.00',
                'description' => 'A comfortable, breathable A-line dress for everyday wear.',
                'points' => 120,
                'ecoInfo' => 'GOTS certified organic cotton.',
                'image' => 'cotton-day-dress.jpg',
            ],
            [
                'name' => 'Hemp Linen Summer Maxi',
                'material' => 'Hemp and Linen Blend',
                'size' => 'Large',
                'color' => Color::BEIGE,
                'price' => '1599.00',
                'cost' => '700.00',
                'description' => 'Flowy maxi dress, naturally anti-bacterial and perfect for hot weather.',
                'points' => 150,
                'ecoInfo' => 'Low-impact hemp and naturally derived linen.',
                'image' => 'hemp-maxi-dress.jpg',
            ],
            [
                'name' => 'Tencel Shirt Dress',
                'material' => 'Tencel Lyocell',
                'size' => 'Small',
                'color' => Color::BLUE,
                'price' => '1899.00',
                'cost' => '850.00',
                'description' => 'Smooth, luxurious shirt dress with a button-down front.',
                'points' => 180,
                'ecoInfo' => 'Tencel is made in a closed-loop system, minimizing waste.',
                'image' => 'tencel-shirt-dress.jpg',
            ],
            [
                'name' => 'Recycled Chiffon Midi',
                'material' => 'Recycled Polyester',
                'size' => 'Medium',
                'color' => Color::BLACK,
                'price' => '1450.00',
                'cost' => '620.00',
                'description' => 'Lightweight midi dress made from upcycled materials.',
                'points' => 140,
                'ecoInfo' => 'Utilizes plastic waste to create beautiful, flowing fabric.',
                'image' => 'recycled-midi.jpg',
            ],
            [
                'name' => 'Bamboo Knit Tunic Dress',
                'material' => 'Bamboo Viscose',
                'size' => 'Extra Large',
                'color' => Color::GREEN,
                'price' => '1150.00',
                'cost' => '500.00',
                'description' => 'Supremely soft and comfortable tunic, naturally thermoregulating.',
                'points' => 110,
                'ecoInfo' => 'Bamboo is a rapidly renewable resource.',
                'image' => 'bamboo-tunic.jpg',
            ],
            [
                'name' => 'Block Print Cotton Sundress',
                'material' => 'Organic Cotton',
                'size' => 'Small',
                'color' => Color::WHITE,
                'price' => '950.00',
                'cost' => '400.00',
                'description' => 'Hand block-printed sundress with natural dyes.',
                'points' => 90,
                'ecoInfo' => 'Handmade with natural, azo-free dyes.',
                'image' => 'block-print-sundress.jpg',
            ],
            [
                'name' => 'Ethical Wool Sweater Dress',
                'material' => 'Recycled Wool',
                'size' => 'Medium',
                'color' => Color::WHITE,
                'price' => '2100.00',
                'cost' => '950.00',
                'description' => 'Warm and cozy sweater dress for cooler days.',
                'points' => 200,
                'ecoInfo' => 'Sourced from pre-consumer recycled wool.',
                'image' => 'wool-sweater-dress.jpg',
            ],
            [
                'name' => 'Tie-Waist Denim Dress',
                'material' => 'Organic Denim',
                'size' => 'Large',
                'color' => Color::BLUE,
                'price' => '1650.00',
                'cost' => '730.00',
                'description' => 'Classic denim look made with eco-friendly washing methods.',
                'points' => 160,
                'ecoInfo' => 'Made with reduced water consumption.',
                'image' => 'denim-dress.jpg',
            ],
            [
                'name' => 'Minimalist V-Neck Slip Dress',
                'material' => 'ECOVERO Viscose',
                'size' => 'Small',
                'color' => Color::BLACK,
                'price' => '1350.00',
                'cost' => '580.00',
                'description' => 'Simple and elegant slip dress, perfect for layering.',
                'points' => 130,
                'ecoInfo' => 'Viscose derived from sustainable wood pulp.',
                'image' => 'viscose-slip-dress.jpg',
            ],
            [
                'name' => 'A-Line Bamboo Print Dress',
                'material' => 'Bamboo Blend',
                'size' => 'Extra Small',
                'color' => Color::RED,
                'price' => '1050.00',
                'cost' => '450.00',
                'description' => 'Brightly printed A-line dress, soft and gentle on the skin.',
                'points' => 100,
                'ecoInfo' => 'Hypoallergenic and naturally dyed.',
                'image' => 'bamboo-print-dress.jpg',
            ],
        ];

        foreach ($productsData as $i => $data) {
            $product = new Product();
            $product->setName($data['name'])
                ->setSubCategory($subCategory)
                ->setMaterial($data['material'])
                ->setSize(Size::from($data['size']))
                ->setColor($data['color'])
                ->setPrice($data['price'])
                ->setCost($data['cost'])
                ->setDescription($data['description'])
                ->setPoints($data['points'])
                ->setEcoInfo($data['ecoInfo'])
                ->setImage($data['image']);

            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            SubCategoryFixtures::class, // Needed for CASUAL_DRESSES_REFERENCE
        ];
    }
}
