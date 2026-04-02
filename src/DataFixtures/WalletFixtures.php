<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Wallet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class WalletFixtures extends Fixture implements DependentFixtureInterface
{
    public const WALLET_REFERENCE = 'wallet';

    public function load(ObjectManager $manager): void
    {
        /** @var Customer $customer */
        $customer = $this->getReference(UserFixtures::CUSTOMER_REFERENCE, Customer::class);

        $wallet = new Wallet();
        $wallet->setBalance('0.00');
        $wallet->setRewardPoints(0);
        $wallet->setCustomer($customer);
        $customer->setWallet($wallet);

        $manager->persist($wallet);
        $manager->flush();

        $this->addReference(self::WALLET_REFERENCE, $wallet);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
