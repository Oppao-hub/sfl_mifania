<?php

namespace App\DataFixtures;

use App\Entity\Wallet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class WalletTransactionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Wallet $wallet */
        $wallet = $this->getReference(WalletFixtures::WALLET_REFERENCE, Wallet::class);

        // 1. Use your actual Entity methods to trigger the math!
        $transaction1 = $wallet->deposit(1000.00, 'Initial deposit');
        $transaction1->setCreatedAt(new \DateTimeImmutable('-2 days'));

        $transaction2 = $wallet->withdraw(150.00, 'Purchase of Eco Bag');
        $transaction2->setCreatedAt(new \DateTimeImmutable('-1 day'));

        // Persist the generated transactions
        $manager->persist($transaction1);
        $manager->persist($transaction2);

        // Flushing will save the transactions AND the updated Wallet balance (850.00)
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            WalletFixtures::class,
        ];
    }
}
