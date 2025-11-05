<?php

namespace App\DataFixtures;

use App\Entity\Wallet;
use App\Entity\WalletTransaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class WalletTransactionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Wallet $wallet */
        $wallet = $this->getReference(WalletFixtures::WALLET_REFERENCE, Wallet::class);

        // Create transactions
        $transaction1 = new WalletTransaction();
        $transaction1->setWallet($wallet);
        $transaction1->setAmount('1000.00');
        $transaction1->setType('deposit');
        $transaction1->setDescription('Initial deposit');
        $transaction1->setCreatedAt(new \DateTimeImmutable('-2 days'));
        $manager->persist($transaction1);

        $transaction2 = new WalletTransaction();
        $transaction2->setWallet($wallet);
        $transaction2->setAmount('-150.00');
        $transaction2->setType('withdrawal');
        $transaction2->setDescription('Purchase of Eco Bag');
        $transaction2->setCreatedAt(new \DateTimeImmutable('-1 day'));
        $manager->persist($transaction2);

        $manager->flush();

    }

    public function getDependencies(): array
    {
        return [
            WalletFixtures::class,
        ];
    }
}
