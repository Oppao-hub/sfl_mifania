<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Enum\Size;
use App\Entity\SubCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\DataFixtures\SubCategoryFixtures; // Added missing import for SubCategoryFixtures
use App\Entity\Enum\Color;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public const PRODUCT_REFERENCE = 'product';

    public function load(ObjectManager $manager): void
    {
        // Get the subcategory reference
        /** @var SubCategory $subCategory */
        // FIX 1: Corrected getReference call (removed second argument)
        $subCategory = $this->getReference(SubCategoryFixtures::BAGS_CATEGORY_REFERENCE, SubCategory::class);

        $productsData = [
            [
                'name' => 'Reusable Bamboo Tote Bag',
                'material' => 'Bamboo Fiber',
                'size' => 'Large',
                'color' => 'Beige',
                'price' => '599.99',
                'cost' => '300.00',
                'description' => 'A durable, eco-friendly tote bag made from bamboo fiber.',
                'points' => 50,
                'ecoInfo' => 'Made from 100% sustainable bamboo materials.',
                'image' => 'bamboo-tote.jpg',
            ],
            [
                'name' => 'Recycled Plastic Bottle Bag',
                'material' => 'Recycled PET',
                'size' => 'Medium',
                'color' => 'Blue',
                'price' => '499.99',
                'cost' => '250.00',
                'description' => 'Stylish everyday bag made from recycled plastic bottles.',
                'points' => 45,
                'ecoInfo' => 'Made from 10 recycled PET bottles.',
                'image' => 'plastic-bag.jpg',
            ],
            [
                'name' => 'Organic Cotton Pouch',
                'material' => 'Organic Cotton',
                'size' => 'Small',
                'color' => 'White',
                'price' => '199.99',
                'cost' => '80.00',
                'description' => 'Soft and reusable cotton pouch, perfect for accessories.',
                'points' => 20,
                'ecoInfo' => 'Certified organic cotton, no synthetic dyes.',
                'image' => 'cotton-pouch.jpg',
            ],
            [
                'name' => 'Hemp Shopping Bag',
                'material' => 'Hemp',
                'size' => 'Large',
                'color' => 'Green',
                'price' => '699.99',
                'cost' => '350.00',
                'description' => 'Strong and stylish hemp shopping bag.',
                'points' => 60,
                'ecoInfo' => 'Hemp grows fast and requires minimal water.',
                'image' => 'hemp-bag.jpg',
            ],
            [
                'name' => 'Foldable Eco Bag',
                'material' => 'Nylon',
                'size' => 'Medium',
                'color' => 'Green',
                'price' => '299.99',
                'cost' => '120.00',
                'description' => 'Compact foldable bag ideal for groceries.',
                'points' => 30,
                'ecoInfo' => 'Reusable and long-lasting alternative to plastic bags.',
                'image' => 'foldable-bag.jpg',
            ],
        ];

        foreach ($productsData as $i => $data) {
            $product = new Product();
            $product->setName($data['name'])
                ->setSubCategory($subCategory)
                ->setMaterial($data['material'])
                ->setSize(Size::from($data['size']))
                ->setColor(Color::from($data['color']))
                ->setPrice($data['price'])
                ->setCost($data['cost'])
                ->setDescription($data['description'])
                ->setPoints($data['points'])
                ->setEcoInfo($data['ecoInfo'])
                ->setImage($data['image']);
            // Removed explicit timestamp setting as Product entity handles it via PrePersist/PreUpdate

            $manager->persist($product);
            $this->addReference(self::PRODUCT_REFERENCE . '-' . $i, $product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
                // SubCategoryFixtures is needed to load the BAGS_CATEGORY_REFERENCE
            CategoryFixtures::class,
            SubCategoryFixtures::class,
        ];
    }
}
