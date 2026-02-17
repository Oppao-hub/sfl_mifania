<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Stock;
use App\Entity\Enum\StockStatus;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StockFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var User $adminUser */
        $adminUser = $this->getReference(UserFixtures::ADMIN_USER_REFERENCE, User::class);

        for ($i = 0; $i < 5; $i++) {
            /** @var Product $product */
            if (!$this->hasReference(ProductFixtures::PRODUCT_REFERENCE . '-' . $i, Product::class))
                continue;

            $product = $this->getReference(ProductFixtures::PRODUCT_REFERENCE . '-' . $i, Product::class);

            $stock = new Stock();
            $stock->setProduct($product);
            $stock->setQuantity(rand(10, 100));
            $stock->setLocation('Warehouse A');
            $stock->setStatus(StockStatus::IN_STOCK);
            $stock->setAddedBy($adminUser);
            $stock->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($stock);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ProductFixtures::class, UserFixtures::class];
    }
}
