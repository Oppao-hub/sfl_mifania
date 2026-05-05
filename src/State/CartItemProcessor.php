<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\CartItem;
use App\Service\CartService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CartItemProcessor implements ProcessorInterface
{
    private CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CartItem
    {
        if (!$data instanceof CartItem) {
            return $data;
        }

        $product = $data->getProduct();
        $quantity = $data->getQuantity() ?? 1;

        if (!$product) {
            throw new BadRequestHttpException('Product is required.');
        }

        // Use the CartService to handle the logic
        $this->cartService->addItem($product, $quantity);

        // We return the updated cart item from the cart
        $cart = $this->cartService->getCart();
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct() === $product) {
                return $item;
            }
        }

        return $data;
    }
}
