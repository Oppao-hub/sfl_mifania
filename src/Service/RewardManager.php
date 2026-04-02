<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\Reward;
use App\Entity\RewardTransaction;
use App\Entity\Redemption;
use Doctrine\ORM\EntityManagerInterface;

class RewardManager
{
    public function __construct(private EntityManagerInterface $em) {}

    public function earnPointsFromOrder(Customer $customer, Order $order, int $pointsToAward): void
    {
        $wallet = $customer->getWallet();
        if (!$wallet) return; // Safety check

        $wallet->setRewardPoints($wallet->getRewardPoints() + $pointsToAward);

        $transaction = new RewardTransaction();
        $transaction->setCustomer($customer);
        $transaction->setOrder($order);
        $transaction->setPoints($pointsToAward);
        $transaction->setType('EARNED');
        $transaction->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($transaction);
        $this->em->flush();
    }

    public function claimReward(Customer $customer, Reward $reward): bool
    {
        $wallet = $customer->getWallet();

        // Check if they have enough points
        if (!$wallet || $wallet->getRewardPoints() < $reward->getPointsRequired()) {
            return false;
        }

        // Deduct points
        $wallet->setRewardPoints($wallet->getRewardPoints() - $reward->getPointsRequired());

        $transaction = new RewardTransaction();
        $transaction->setCustomer($customer);
        $transaction->setPoints(-$reward->getPointsRequired());
        $transaction->setType('REDEEMED');
        $transaction->setCreatedAt(new \DateTimeImmutable());

        $redemption = new Redemption();
        $redemption->setCustomer($customer);
        $redemption->setReward($reward);
        $redemption->setRedeemedAt(new \DateTimeImmutable());

        $this->em->persist($transaction);
        $this->em->persist($redemption);
        $this->em->flush();

        return true;
    }
}
