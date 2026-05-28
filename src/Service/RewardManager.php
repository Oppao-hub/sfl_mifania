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
    public function __construct(
        private EntityManagerInterface $em,
        private int $pointsPerCurrency,
        private float $minOrderForRedemption,
        private float $maxRedemptionPercentage
    ) {}

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

    public function consumePoints(Customer $customer, int $points, ?Order $order = null, string $type = 'REDEEMED'): bool
    {
        $wallet = $customer->getWallet();
        if (!$wallet || $points <= 0 || $wallet->getRewardPoints() < $points) {
            return false;
        }

        $wallet->setRewardPoints($wallet->getRewardPoints() - $points);

        $transaction = new RewardTransaction();
        $transaction->setCustomer($customer);
        $transaction->setOrder($order);
        $transaction->setPoints(-$points);
        $transaction->setType($type);
        $transaction->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($transaction);

        return true;
    }

    public function currencyDiscountFromPoints(int $points): string
    {
        if ($points <= 0) {
            return '0.00';
        }

        return number_format($points / max(1, $this->pointsPerCurrency), 2, '.', '');
    }

    public function pointsFromDiscount(float $discount): int
    {
        if ($discount <= 0) {
            return 0;
        }

        return (int) floor($discount * max(1, $this->pointsPerCurrency));
    }

    public function refundPointsForOrder(Order $order): bool
    {
        $pointsRedeemed = (int) ($order->getPointsRedeemed() ?? 0);
        $customer = $order->getCustomer();
        if ($pointsRedeemed <= 0 || !$customer) {
            return false;
        }

        $existingRefund = $this->em->getRepository(RewardTransaction::class)->findOneBy([
            'order' => $order,
            'type' => 'REFUNDED',
        ]);

        if ($existingRefund) {
            return false;
        }

        $wallet = $customer->getWallet();
        if (!$wallet) {
            return false;
        }

        $wallet->setRewardPoints($wallet->getRewardPoints() + $pointsRedeemed);

        $transaction = new RewardTransaction();
        $transaction->setCustomer($customer);
        $transaction->setOrder($order);
        $transaction->setPoints($pointsRedeemed);
        $transaction->setType('REFUNDED');
        $transaction->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($transaction);

        return true;
    }

    public function getPointsPerCurrency(): int
    {
        return max(1, $this->pointsPerCurrency);
    }

    public function maxDiscountForOrder(float $orderAmount): float
    {
        if ($orderAmount < $this->minOrderForRedemption) {
            return 0.0;
        }

        return $orderAmount * $this->maxRedemptionPercentage;
    }
}
