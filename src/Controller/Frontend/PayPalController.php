<?php

namespace App\Controller\Frontend;

use App\Service\PayPalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PayPalController extends AbstractController
{
    public function __construct(
        private readonly string $paypalClientId,
        private readonly string $paypalMode,
    ) {
    }

    #[Route('/paypal/payment', name: 'paypal_payment')]
    public function payment(Request $request): Response
    {
        $amount = max(0.01, (float) $request->query->get('amount', 10.00));
        $purpose = strtolower(trim((string) $request->query->get('purpose', 'order')));
        $prepareToken = trim((string) $request->query->get('prepare', ''));

        $mode = strtolower(trim($this->paypalMode));
        $isWalletTopUp = $purpose === 'wallet' && $prepareToken !== '';

        return $this->render('paypal/payment.html.twig', [
            'amount' => number_format($amount, 2, '.', ''),
            'paypalClientId' => $this->paypalClientId,
            'paypalConfigured' => $this->paypalClientId !== '',
            'paypalMode' => $mode !== '' ? $mode : 'sandbox',
            'isSandbox' => $mode === '' || str_contains($mode, 'sandbox'),
            'isWalletTopUp' => $isWalletTopUp,
            'prepareToken' => $prepareToken,
            'walletCompleteUrl' => $this->generateUrl('api_wallet_paypal_complete'),
        ]);
    }

    #[Route('/paypal/create-order', name: 'paypal_create_order', methods: ['POST'])]
    public function createOrder(Request $request, PayPalService $paypalService): JsonResponse
    {
        if ($this->paypalClientId === '') {
            return new JsonResponse(['error' => 'PayPal is not configured on the server.'], 503);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $rawAmount = $data['amount'] ?? null;

        if (!is_numeric($rawAmount)) {
            return new JsonResponse(['error' => 'Invalid amount.'], 400);
        }

        $amount = (float) $rawAmount;
        if ($amount <= 0) {
            return new JsonResponse(['error' => 'Amount must be greater than zero.'], 422);
        }

        try {
            $orderId = $paypalService->createOrder($amount);

            return new JsonResponse(['id' => $orderId]);
        } catch (\Throwable $exception) {
            return new JsonResponse(
                [
                    'error' => 'Unable to create PayPal order. Please try again.',
                    'detail' => $exception->getMessage(),
                ],
                502,
            );
        }
    }

    #[Route('/paypal/capture-order', name: 'paypal_capture_order', methods: ['POST'])]
    public function captureOrder(Request $request, PayPalService $paypalService): JsonResponse
    {
        if ($this->paypalClientId === '') {
            return new JsonResponse(['error' => 'PayPal is not configured on the server.'], 503);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $orderId = $data['orderID'] ?? null;
        if (!is_string($orderId) || $orderId === '') {
            return new JsonResponse(['error' => 'Missing orderID.'], 400);
        }

        try {
            $result = $paypalService->captureOrder($orderId);

            return new JsonResponse($result);
        } catch (\Throwable $exception) {
            return new JsonResponse(
                [
                    'error' => 'Unable to capture PayPal payment. Please try again.',
                    'detail' => $exception->getMessage(),
                ],
                502,
            );
        }
    }
}
