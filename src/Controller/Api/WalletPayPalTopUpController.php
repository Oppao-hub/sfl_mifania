<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\WalletPayPalTopUpService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WalletPayPalTopUpController extends AbstractController
{
    #[Route('/api/wallet/paypal/prepare', name: 'api_wallet_paypal_prepare', methods: ['POST'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function prepare(Request $request, WalletPayPalTopUpService $topUpService, #[CurrentUser] User $user): JsonResponse
    {
        $customer = $user->getCustomer();
        if (!$customer) {
            throw new BadRequestHttpException('Customer profile not found.');
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !is_numeric($payload['amount'] ?? null)) {
            throw new BadRequestHttpException('A valid amount is required.');
        }

        $amount = round((float) $payload['amount'], 2);
        $result = $topUpService->prepare($customer, $amount);

        return $this->json($result);
    }

    #[Route('/api/wallet/paypal/complete', name: 'api_wallet_paypal_complete', methods: ['POST'])]
    public function complete(Request $request, WalletPayPalTopUpService $topUpService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid request body.');
        }

        $prepareToken = trim((string) ($payload['prepareToken'] ?? ''));
        $paypalOrderId = trim((string) ($payload['paypalOrderId'] ?? $payload['orderID'] ?? ''));

        try {
            $result = $topUpService->complete($prepareToken, $paypalOrderId);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (\RuntimeException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $this->json($result);
    }
}
