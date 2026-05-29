<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\WalletTopUpIntent;
use App\Repository\WalletTopUpIntentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class WalletPayPalTopUpService
{
    private const INTENT_TTL_HOURS = 2;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private WalletTopUpIntentRepository $intentRepository,
        private WalletManager $walletManager,
        private PayPalService $payPalService,
    ) {
    }

    /**
     * @return array{prepareToken: string, amount: string}
     */
    public function prepare(Customer $customer, float $amount): array
    {
        $this->assertValidAmount($amount);

        if (!$this->payPalService->isConfigured()) {
            throw new BadRequestHttpException('PayPal is not configured on the server.');
        }

        $intent = new WalletTopUpIntent();
        $intent->setCustomer($customer);
        $intent->setAmount(number_format($amount, 2, '.', ''));
        $intent->setPrepareToken(bin2hex(random_bytes(32)));

        $this->entityManager->persist($intent);
        $this->entityManager->flush();

        return [
            'prepareToken' => $intent->getPrepareToken(),
            'amount' => $intent->getAmount(),
        ];
    }

    /**
     * @return array{message: string, balance: string, rewardPoints: int}
     */
    public function complete(string $prepareToken, string $paypalOrderId): array
    {
        $prepareToken = trim($prepareToken);
        $paypalOrderId = trim($paypalOrderId);

        if ($prepareToken === '' || $paypalOrderId === '') {
            throw new BadRequestHttpException('prepareToken and paypalOrderId are required.');
        }

        $existing = $this->intentRepository->findByPaypalOrderId($paypalOrderId);
        if ($existing && $existing->getStatus() === WalletTopUpIntent::STATUS_COMPLETED) {
            $wallet = $existing->getCustomer()?->getWallet();
            if (!$wallet) {
                throw new BadRequestHttpException('Wallet not found.');
            }

            return [
                'message' => 'Wallet top-up already completed.',
                'balance' => $wallet->getBalance(),
                'rewardPoints' => $wallet->getRewardPoints() ?? 0,
            ];
        }

        $intent = $this->intentRepository->findPendingByPrepareToken($prepareToken);
        if (!$intent) {
            throw new BadRequestHttpException('Top-up session expired or invalid. Start again from the app.');
        }

        if ($this->isExpired($intent)) {
            throw new BadRequestHttpException('Top-up session expired. Start again from the app.');
        }

        $customer = $intent->getCustomer();
        $wallet = $customer?->getWallet();
        if (!$customer || !$wallet) {
            throw new BadRequestHttpException('Wallet not found.');
        }

        $expectedAmount = (float) $intent->getAmount();
        $paidAmount = $this->payPalService->resolveCapturedAmount($paypalOrderId, $expectedAmount);

        if (abs($paidAmount - $expectedAmount) > 0.01) {
            throw new BadRequestHttpException('PayPal payment amount does not match the requested top-up.');
        }

        return $this->entityManager->wrapInTransaction(function () use ($intent, $wallet, $paypalOrderId, $paidAmount): array {
            $intent->setPaypalOrderId($paypalOrderId);
            $intent->markCompleted();

            $this->walletManager->topUp(
                $wallet,
                $paidAmount,
                sprintf('PayPal top-up (%s)', $paypalOrderId),
            );

            $this->entityManager->flush();

            return [
                'message' => 'Wallet topped up successfully via PayPal.',
                'balance' => $wallet->getBalance(),
                'rewardPoints' => $wallet->getRewardPoints() ?? 0,
            ];
        });
    }

    private function assertValidAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new BadRequestHttpException('Amount must be greater than zero.');
        }

        if ($amount > WalletManager::MAX_AMOUNT) {
            throw new ConflictHttpException(
                sprintf('Amount cannot exceed ₱%s.', number_format(WalletManager::MAX_AMOUNT, 2)),
            );
        }
    }

    private function isExpired(WalletTopUpIntent $intent): bool
    {
        $createdAt = $intent->getCreatedAt();
        if (!$createdAt) {
            return true;
        }

        return $createdAt < new \DateTimeImmutable(sprintf('-%d hours', self::INTENT_TTL_HOURS));
    }
}
