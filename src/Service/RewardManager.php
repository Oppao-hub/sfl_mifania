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
    public const POINTS_PER_CURRENCY = 10;
    public const MIN_ORDER_FOR_REDEMPTION = 100.00;
    public const MAX_REDEMPTION_PERCENTAGE = 0.30;

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
        $pointsRequired = $reward->getPointsRequired() ?? 0;
        if (!$wallet || $pointsRequired <= 0 || !$this->consumePoints($customer, $pointsRequired)) {
            return false;
        }

        $redemption = new Redemption();
        $redemption->setCustomer($customer);
        $redemption->setReward($reward);
        $redemption->setPointSpent($pointsRequired);
        $redemption->setRedeemedAt(new \DateTimeImmutable());
        $redemption->setStatus('PENDING');

        $this->em->persist($redemption);
        $this->em->flush();

        return true;
    }

    public function consumePoints(Customer $customer, int $points): bool
    {
        $wallet = $customer->getWallet();
        if (!$wallet || $points <= 0 || $wallet->getRewardPoints() < $points) {
            return false;
        }

        $wallet->setRewardPoints($wallet->getRewardPoints() - $points);

        $transaction = new RewardTransaction();
        $transaction->setCustomer($customer);
        $transaction->setPoints(-$points);
        $transaction->setType('REDEEMED');
        $transaction->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($transaction);

        return true;
    }

    public function currencyDiscountFromPoints(int $points): string
    {
        if ($points <= 0) {
            return '0.00';
        }

        return number_format($points / self::POINTS_PER_CURRENCY, 2, '.', '');
    }

    public function maxDiscountForOrder(float $orderAmount): float
    {
        if ($orderAmount < self::MIN_ORDER_FOR_REDEMPTION) {
            return 0.0;
        }

        return $orderAmount * self::MAX_REDEMPTION_PERCENTAGE;
    }
}
