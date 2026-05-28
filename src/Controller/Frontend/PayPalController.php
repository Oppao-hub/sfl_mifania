<?php

namespace App\Controller\Frontend;

use App\Service\PayPalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class PayPalController extends AbstractController
{
    private PayPalService $paypalService;

    public function __construct(PayPalService $paypalService)
    {
        $this->paypalService = $paypalService;
    }

    #[Route('/paypal/payment', name: 'paypal_payment')]
    public function payment(Request $request)
    {
        $amount = max(0.01, (float) $request->query->get('amount', 10.00));
        return $this->render('paypal/payment.html.twig', [
            'amount' => number_format($amount, 2, '.', ''),
        ]);
    }

    #[Route('/paypal/create-order', name: 'paypal_create_order', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $rawAmount = $data['amount'] ?? null;

        if (!is_numeric($rawAmount)) {
            return new JsonResponse(['error' => 'Invalid amount.'], 400);
        }

        $amount = (float) $rawAmount;
        if ($amount <= 0) {
            return new JsonResponse(['error' => 'Amount must be greater than zero.'], 422);
        }

        $orderId = $this->paypalService->createOrder($amount);
        return new JsonResponse(['id' => $orderId]);
    }

    #[Route('/paypal/capture-order', name: 'paypal_capture_order', methods: ['POST'])]
    public function captureOrder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $orderId = $data['orderID'] ?? null;
        if (!is_string($orderId) || $orderId === '') {
            return new JsonResponse(['error' => 'Missing orderID.'], 400);
        }

        $result = $this->paypalService->captureOrder($orderId);
        return new JsonResponse($result);
    }
}
