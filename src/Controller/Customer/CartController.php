<?php

namespace App\Controller\Customer;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/cart')]
final class CartController extends AbstractController
{
    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function addToCart(
        Product $product,
        CartService $cartService
    ): Response {
        $cartService->addItem($product);

        $this->addFlash('success', 'Product added to cart!');

        return $this->redirectToRoute('app_cart_show');
    }

    /**
     * Displays the contents of the cart.
     */
    #[Route('/cart', name: 'app_cart_show', methods: ['GET'])]
    public function show(CartService $cartService): Response
    {
        $cart = $cartService->getCart();
        $total = $cartService->getTotal();

        return $this->render('customer/cart/show.html.twig', [
            'cart' => $cart,
            'total' => $total,
        ]);
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(
        CartItem $item,
        CartService $cartService
    ): Response {
        $cartService->removeItem($item);

        $this->addFlash('info', 'Product removed from cart.');

        return $this->redirectToRoute('app_cart_show');
    }
}
