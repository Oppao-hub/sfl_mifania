<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Service\CartService;
use Symfony\Bundle\SecurityBundle\Security;

class CartsProvider implements ProviderInterface
{
    private CartService $cartService;
    private Security $security;

    public function __construct(CartService $cartService, Security $security)
    {
        $this->cartService = $cartService;
        $this->security = $security;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return $this->cartService->getCustomerCarts();
    }
}
