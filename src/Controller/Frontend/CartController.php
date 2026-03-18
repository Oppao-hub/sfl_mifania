<?php

namespace App\Controller\Frontend;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Service\CartService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
final class CartController extends AbstractController
{
    // Fix: Added the missing '/' path
    #[Route('/', name: 'app_cart_index', methods: ['GET'])]
    public function index(CartService $cartService): Response
    {
        // Redirect index to show so there's one single source of truth
        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['GET', 'POST'])]
    public function addToCart(
        Product $product,
        CartService $cartService,
        Request $request
    ): Response {
        $quantity = (int) $request->request->get('quantity', 1);
        $action = $request->request->get('action', 'add_to_cart');

        $cartService->addItem($product, $quantity);

        $this->addFlash('success', 'Product added to cart!');

        return $this->redirectToRoute($action === 'buy_now' ? 'app_checkout' : 'app_cart_show');
    }

    #[Route('/show', name: 'app_cart_show', methods: ['GET'])]
    public function show(CartService $cartService): Response
    {
        $cart = $cartService->getCart();

        return $this->render('frontend/cart/show.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(CartItem $item, CartService $cartService): Response
    {
        $cartService->removeItem($item);
        $this->addFlash('info', 'Product removed from cart.');

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(CartService $cartService): Response
    {
        $cartService->clearCart();
        $this->addFlash('info', 'Your shopping cart has been cleared.');

        return $this->redirectToRoute('app_cart_show');
    }
}
