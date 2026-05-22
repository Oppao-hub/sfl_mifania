<?php

namespace App\DataFixtures;

use App\Entity\Cart;
use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CartFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Customer $customer */
        $customer = $this->getReference(UserFixtures::CUSTOMER_REFERENCE, Customer::class);

        $cart = new Cart();
        $cart->setCustomer($customer);
        // If your Cart entity requires defaults (like totalQuantity = 0), set them here

        $manager->persist($cart);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class, // Cart depends on the Customer existing first
        ];
    }
}
