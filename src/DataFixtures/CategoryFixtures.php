<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const BAGS_CATEGORY_REFERENCE = 'category-bags';

    public function load(ObjectManager $manager): void
    {
        $subcategories = [
            ['Tops & Shirts', 'Eco-friendly tops for everyday wear'],
            ['Pants & Bottoms', 'Sustainable trousers and skirts'],
            ['Dresses & Skirts', 'Stylish dresses made from organic fabrics'],
            ['Outerwear', 'Jackets and coats from recycled materials'],
            ['Activewear', 'Comfortable, sustainable activewear'],
            ['Casual Shoes', 'Everyday shoes crafted responsibly'],
            ['Sneakers', 'Trend-conscious sneakers made sustainably'],
            ['Sandals', 'Lightweight, eco-friendly sandals'],
            ['Boots', 'Durable boots with eco-conscious materials'],
            ['Bags & Backpacks', 'Functional bags from recycled materials'],
            ['Hats & Caps', 'Eco-friendly headwear'],
            ['Scarves & Gloves', 'Sustainable accessories for daily use'],
            ['Jewelry', 'Ethically sourced or recycled jewelry'],
            ['Reusable Masks', 'Comfortable, eco-conscious masks'],
            ['Water Bottles & Gear', 'Sustainable lifestyle essentials'],
            ['Rain Gear & Outerwear', 'Eco-friendly raincoats and ponchos'],
            ['Upcycled / Recycled', 'Fashion made from repurposed materials'],
            ['Organic Cotton', 'Clothes made from certified organic cotton'],
            ['Vegan Leather', 'Animal-free leather alternatives'],
        ];

        foreach ($subcategories as [$name, $description]) {
            $category = new Category();
            $category->setName($name);
            $category->setDescription($description);
            $category->setCreatedAt(new \DateTimeImmutable());
            $category->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($category);

            // Add a reference for the specific category we need in other fixtures
            if ($name === 'Bags & Backpacks') {
                $this->addReference(self::BAGS_CATEGORY_REFERENCE, $category);
            }
        }

        $manager->flush();
    }
}
