<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Enum\Color;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\Enum\Size;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CartService
{
    private EntityManagerInterface $em;
    private RequestStack $requestStack;
    private Security $security;

    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, Security $security)
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    private function getSession()
    {
        try {
            return $this->requestStack->getSession();
        } catch (\Symfony\Component\HttpFoundation\Exception\SessionNotFoundException $e) {
            return null;
        }
    }

    public function getCart(?int $cartId = null): Cart
    {
        $user = $this->security->getUser();
        $customer = ($user instanceof User) ? $user->getCustomer() : null;
        $cart = null;
        $session = $this->getSession();

        // 1. If a specific ID is provided, try to find it (and check ownership)
        if ($cartId) {
            $cart = $this->em->getRepository(Cart::class)->find($cartId);
            if ($cart && $customer && $cart->getCustomer() !== $customer) {
                $cart = null; // Unauthorized or wrong cart
            }
        }

        // 2. If logged in and no cart yet, prioritize their main database cart
        if (!$cart && $customer) {
            $cart = $this->em->getRepository(Cart::class)->findOneBy([
                'customer' => $customer,
                'isMain' => true
            ]);
        }

        // 3. Check session for a guest cart ID if still no cart
        if (!$cart && $session) {
            $sessionCartId = $session->get('cartId');
            if ($sessionCartId) {
                $cart = $this->em->getRepository(Cart::class)->find($sessionCartId);
            }
        }

        // 4. Create new main cart if none exists
        if (!$cart) {
            $cart = $this->createCart($customer, 'Main Cart', true);
            
            if ($session) {
                $session->set('cartId', $cart->getId());
            }
        }

        // 5. Merge guest cart if user just logged in
        if ($cart->getCustomer() === null && $customer) {
            $this->mergeGuestCart($cart, $customer);
        }

        return $cart;
    }

    public function createCart($customer = null, string $name = 'New Collection', bool $isMain = false): Cart
    {
        // If this is the first cart or set to main, ensure others are not main
        if ($isMain && $customer) {
            $this->clearMainCartFlag($customer);
        }

        $cart = new Cart();
        $cart->setName($name);
        $cart->setIsMain($isMain);
        if ($customer) {
            $cart->setCustomer($customer);
        }

        $this->em->persist($cart);
        $this->em->flush();

        return $cart;
    }

    public function switchMainCart(Cart $newMainCart): void
    {
        $customer = $newMainCart->getCustomer();
        if (!$customer) {
            return;
        }

        $this->clearMainCartFlag($customer);
        $newMainCart->setIsMain(true);
        $this->em->persist($newMainCart);
        $this->em->flush();
        
        $session = $this->getSession();
        if ($session) {
            $session->set('cartId', $newMainCart->getId());
        }
    }

    public function getCustomerCarts(): array
    {
        $user = $this->security->getUser();
        $customer = ($user instanceof User) ? $user->getCustomer() : null;

        if (!$customer) {
            return [];
        }

        return $this->em->getRepository(Cart::class)->findBy(['customer' => $customer], ['updatedAt' => 'DESC']);
    }

    public function addItem(Product $product, int $quantity = 1, ?int $cartId = null): void
    {
        $cart = $this->getCart($cartId);
        $isExistingItem = false;
        $cartItem = null;

        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct() === $product) {
                $isExistingItem = true;
                $cartItem = $item;
                break;
            }
        }

        if ($isExistingItem && $cartItem !== null) {
            $newQuantity = $cartItem->getQuantity() + $quantity;
            $cartItem->setQuantity($newQuantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cartItem->setPrice($product->getPrice());

            $this->em->persist($cartItem);
            $cart->addCartItem($cartItem);
        }

        $this->recalculateCartTotals($cart);
        $this->em->persist($cart);
        $this->em->flush();
    }

    public function removeItem(CartItem $cartItem): void
    {
        $cart = $cartItem->getCart();

        if ($cart) {
            $cart->removeCartItem($cartItem);
        }

        $this->em->remove($cartItem);

        if ($cart) {
            $this->recalculateCartTotals($cart);
            $this->em->persist($cart);
        }

        $this->em->flush();
    }

    public function getTotal(): float
    {
        $cart = $this->getCart();
        return (float) $cart->getTotalPrice();
    }

    public function clearCart(?int $cartId = null): void
    {
        $cart = $this->getCart($cartId);

        foreach ($cart->getCartItems() as $item) {
            $this->em->remove($item);
        }

        $cart->getCartItems()->clear();
        $cart->setTotalQuantity(0);
        $cart->setTotalPrice('0.00');

        $this->em->persist($cart);
        $this->em->flush();
    }

    private function mergeGuestCart(Cart $cart, $customer): void
    {
        $existingMainCart = $this->em->getRepository(Cart::class)->findOneBy([
            'customer' => $customer,
            'isMain' => true
        ]);

        if ($existingMainCart && $existingMainCart->getId() !== $cart->getId()) {
            // Transfer items from guest cart to existing main cart
            foreach ($cart->getCartItems() as $item) {
                $this->addItem($item->getProduct(), $item->getQuantity(), $existingMainCart->getId());
            }
            // Remove guest cart
            $this->em->remove($cart);
            $session = $this->getSession();
            if ($session) {
                $session->set('cartId', $existingMainCart->getId());
            }
        } else {
            // Just attach guest cart to user as main
            $cart->setCustomer($customer);
            $cart->setIsMain(true);
            $this->em->persist($cart);
        }

        $this->em->flush();
    }

    private function clearMainCartFlag($customer): void
    {
        $carts = $this->em->getRepository(Cart::class)->findBy(['customer' => $customer, 'isMain' => true]);
        foreach ($carts as $c) {
            $c->setIsMain(false);
            $this->em->persist($c);
        }
    }

    private function recalculateCartTotals(Cart $cart): void
    {
        $totalQuantity = 0;
        $totalPrice = 0.00;

        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()) {
                $subtotal = (float)$item->getProduct()->getPrice() * $item->getQuantity();
                $item->setSubtotal(number_format($subtotal, 2, '.', ''));
            }

            $totalQuantity += $item->getQuantity();
            $totalPrice += (float) $item->getSubtotal();
        }

        $cart->setTotalQuantity($totalQuantity);
        $cart->setTotalPrice(number_format($totalPrice, 2, '.', ''));
    }
}
