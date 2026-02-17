<?php

namespace App\Controller\Customer;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Service\CartService;
use App\Form\AddToCartType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
final class CartController extends AbstractController
{
    #[Route('/add/{slug}', name: 'app_cart_add', methods: ['POST'])]
    public function addToCart(
        Product $product,
        CartService $cartService,
        Request $request
    ): Response {

        $quantity = (int) $request->request->get('quantity', 1);
        $action = $request->request->get('action', 'add_to_cart');

        $cartService->addItem(
            $product,
            $quantity,
        );

        $redirectRoute = $action === 'buy_now'
            ? 'app_checkout'
            : 'app_cart_show';


        $this->addFlash('success', 'Product added to cart!');
        return $this->redirectToRoute($redirectRoute);
    }

    #[Route('/show', name: 'app_cart_show', methods: ['GET'])]
    public function show(CartService $cartService): Response
    {
        $cart = $cartService->getCart();
        $total = $cartService->getTotal();

        return $this->render('customer/cart/show.html.twig', [
            'cart' => $cart,
            'total' => $total,
        ]);
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(
        CartItem $item,
        CartService $cartService
    ): Response {

        $cartService->removeItem($item);

        $this->addFlash('info', 'Product removed from cart.');
        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(
        CartService $cartService
    ): Response {

        $cartService->clearCart();

        $this->addFlash('info', 'Your shopping cart had been cleared.');
        return $this->redirectToRoute('app_cart_show');
    }
}
