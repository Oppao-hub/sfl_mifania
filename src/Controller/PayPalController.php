<?php

namespace App\Controller;

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
    public function payment()
    {
        return $this->render('paypal/payment.html.twig');
    }

    #[Route('/paypal/create-order', name: 'paypal_create_order', methods: ['POST'])]
    public function createOrder(): JsonResponse
    {
        $orderId = $this->paypalService->createOrder(10.00); // amount
        return new JsonResponse(['id' => $orderId]);
    }

    #[Route('/paypal/capture-order', name: 'paypal_capture_order', methods: ['POST'])]
    public function captureOrder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $result = $this->paypalService->captureOrder($data['orderID']);
        return new JsonResponse($result);
    }
}
