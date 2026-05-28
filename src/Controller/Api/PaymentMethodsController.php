<?php

namespace App\Controller\Api;

use App\Entity\Enum\PaymentMethod;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/payment-methods')]
class PaymentMethodsController extends AbstractController
{
    #[Route('', name: 'api_payment_methods_get', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $methods = array_map(
            static fn (PaymentMethod $method) => self::toOption($method),
            PaymentMethod::cases(),
        );

        return $this->json([
            '@context' => '/api/contexts/PaymentMethod',
            '@type' => 'hydra:Collection',
            'hydra:member' => $methods,
            'hydra:totalItems' => count($methods),
        ]);
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     backendMethod: string,
     *     gatewayType: string,
     *     description: string
     * }
     */
    private static function toOption(PaymentMethod $method): array
    {
        $value = $method->value;

        return [
            'id' => strtolower(str_replace(' ', '-', $value)),
            'name' => $value,
            'backendMethod' => $value,
            'gatewayType' => $method === PaymentMethod::PAYPAL ? 'paypal' : 'direct',
            'description' => match ($method) {
                PaymentMethod::CASH => 'Pay securely upon delivery',
                PaymentMethod::BANK_TRANSFER => 'Transfer to our bank account — details sent after order',
                PaymentMethod::PAYPAL => 'Pay with your PayPal account',
                PaymentMethod::CREDIT => 'Pay with Visa, Mastercard, or other major cards',
            },
        ];
    }
}
