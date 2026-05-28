<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\WalletManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/wallet')]
#[IsGranted('ROLE_CUSTOMER')]
class WalletController extends AbstractController
{
    #[Route('/top-up', name: 'api_wallet_top_up', methods: ['POST'])]
    public function topUp(Request $request, WalletManager $walletManager, #[CurrentUser] User $user): JsonResponse
    {
        $wallet = $this->resolveCustomerWallet($user);
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid request body.');
        }

        $amount = $this->parseAmount($payload['amount'] ?? null);

        try {
            $walletManager->topUp($wallet, $amount, isset($payload['description']) ? trim((string) $payload['description']) : null);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $this->walletResponse($wallet, 'Wallet topped up successfully.');
    }

    #[Route('/transfer', name: 'api_wallet_transfer', methods: ['POST'])]
    public function transfer(Request $request, WalletManager $walletManager, #[CurrentUser] User $user): JsonResponse
    {
        $wallet = $this->resolveCustomerWallet($user);
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid request body.');
        }

        $recipientEmail = trim((string) ($payload['recipientEmail'] ?? ''));
        if ($recipientEmail === '') {
            throw new BadRequestHttpException('Recipient email is required.');
        }

        $amount = $this->parseAmount($payload['amount'] ?? null);

        try {
            $walletManager->transfer(
                $wallet,
                $recipientEmail,
                $amount,
                isset($payload['note']) ? trim((string) $payload['note']) : null,
            );
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $this->walletResponse($wallet, 'Transfer completed successfully.');
    }

    private function resolveCustomerWallet(User $user): \App\Entity\Wallet
    {
        $customer = $user->getCustomer();
        $wallet = $customer?->getWallet();

        if (!$wallet) {
            throw new BadRequestHttpException('Wallet not found.');
        }

        return $wallet;
    }

    private function parseAmount(mixed $value): float
    {
        if (!is_numeric($value)) {
            throw new BadRequestHttpException('A valid amount is required.');
        }

        return round((float) $value, 2);
    }

    private function walletResponse(\App\Entity\Wallet $wallet, string $message): JsonResponse
    {
        return $this->json([
            'message' => $message,
            'balance' => $wallet->getBalance(),
            'rewardPoints' => $wallet->getRewardPoints(),
        ]);
    }
}
