<?php

namespace App\DataFixtures;

use App\Entity\SubCategory;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface; // 👈 1. Add this use statement
use Doctrine\Persistence\ObjectManager;

// 👈 2. Implement the interface here
class SubCategoryFixtures extends Fixture implements DependentFixtureInterface
{
    public const BAGS_CATEGORY_REFERENCE = 'category-bags';
    public const CASUAL_DRESSES_REFERENCE = 'subcategory-casual-dresses';

    public function load(ObjectManager $manager): void
    {

        $subcategories = [
            ['Tops & Shirts', 'Organic cotton blouses, hemp tees, and recycled fabric tops.'],
            ['Pants & Bottoms', 'Ethically produced bottoms that combine durability and comfort.'],
            ['Skirts', 'Feminine, minimalist designs made from eco-textiles.'],
            ['Outerwear', 'Jackets, blazers, and coats crafted from recycled or plant-based fibers.'],
            ['Knitwear', 'Cozy sweaters and cardigans made from sustainable wool or bamboo yarn.'],

            ['Casual Dresses', 'Everyday essentials made from breathable organic cotton and linen.'],
            ['Evening Dresses', 'Elegant, sustainably sourced pieces for special occasions.'],
            ['Summer Dresses', 'Lightweight, flowy designs perfect for warm weather.'],
            ['Workwear Dresses', 'Professional and minimalist styles for office or business wear.'],
            ['Maxi & Midi Dresses', 'Timeless silhouettes that combine sustainability with sophistication.'],

            ['Sneakers', 'Comfortable and stylish shoes made from recycled plastics or organic cotton.'],
            ['Sandals', 'Light, breathable, and biodegradable footwear for everyday wear.'],
            ['Flats', 'Classic eco-chic options for casual or office looks.'],
            ['Boots', 'Ethically made leather-free or responsibly sourced options for cooler seasons.'],
            ['Heels', 'Elegant, sustainable heels designed with comfort and environmental care in mind.'],

            ['Bags & Backpacks', 'Handbags, totes, and backpacks made from recycled or vegan materials.'],
            ['Jewelry', 'Ethically sourced and handmade pieces using recycled metals and fair-trade gems.'],
            ['Hats & Scarves', 'Natural fiber accents for comfort and style.'],
            ['Belts', 'Durable, minimalist designs made from cork, hemp, or upcycled leather.'],
            ['Sunglasses', 'Eco-conscious eyewear crafted from bamboo or recycled plastic frames.']
        ];

        foreach ($subcategories as [$name, $description]) {
            $subCategory = new SubCategory();
            $subCategory->setName($name);
            $subCategory->setDescription($description);
            $subCategory->setCreatedAt(new \DateTimeImmutable());
            $subCategory->setUpdatedAt(new \DateTimeImmutable());

            if (in_array($name, ['Tops & Shirts', 'Pants & Bottoms', 'Skirts', 'Outerwear', 'Knitwear'])) {
                $category = $this->getReference(CategoryFixtures::CLOTHING_CATEGORY_REFERENCE, Category::class);
            } elseif (str_contains($name, 'Dress')) {
                $category = $this->getReference(CategoryFixtures::DRESSES_CATEGORY_REFERENCE, Category::class);
            } elseif (in_array($name, ['Sneakers', 'Sandals', 'Flats', 'Boots', 'Heels'])) {
                $category = $this->getReference(CategoryFixtures::SHOES_CATEGORY_REFERENCE, Category::class);
            } else {
                $category = $this->getReference(CategoryFixtures::ACCESSORIES_CATEGORY_REFERENCE, Category::class);
            }

            $subCategory->setCategory($category);
            $manager->persist($subCategory);


            // Add a reference for the specific category we need in other fixtures
            if ($name === 'Bags & Backpacks') {
                $this->addReference(self::BAGS_CATEGORY_REFERENCE, $subCategory);
            }

            if ($name === 'Casual Dresses') {
                $this->addReference(self::CASUAL_DRESSES_REFERENCE, $subCategory);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}
