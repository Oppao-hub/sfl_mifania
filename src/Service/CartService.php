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
    private SessionInterface $session;
    private Security $security;

    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, Security $security)
    {
        $this->em = $em;
        $this->session = $requestStack->getSession();
        $this->security = $security;
    }

    public function getCart(): Cart
    {
        $user = $this->security->getUser();
        $customer = ($user instanceof User) ? $user->getCustomer() : null;
        $sessionCartId = $this->session->get('cartId');
        $cart = null;

        // If user has customer record, try to load their cart first
        if ($customer) {
            $cart = $this->em->getRepository(Cart::class)->findOneBy(['customer' => $customer]);

            if ($cart) {
                $this->session->set('cartId', $cart->getId());
            }
        }

        // If there's a cart id in session, prefer that (overrides previous)
        if ($sessionCartId) {
            $cartFromSession = $this->em->getRepository(Cart::class)->find($sessionCartId);
            if ($cartFromSession) {
                $cart = $cartFromSession;
            }
        }

        // Create new cart if none found
        if (!$cart) {
            $cart = new Cart();

            if ($customer) {
                $cart->setCustomer($customer);
            }

            $this->em->persist($cart);
            $this->em->flush();
            $this->session->set('cartId', $cart->getId());
        }

        // If cart has no customer but user does, attach and persist
        if ($cart->getCustomer() === null && $customer) {
            $cart->setCustomer($customer);
            $this->em->persist($cart);
            $this->em->flush();
        }

        return $cart;
    }

    public function addItem(Product $product, int $quantity = 1): void
    {
        $cart = $this->getCart();
        $isExistingItem = false;
        $cartItem = null;

        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct() === $product) {
                $isExistingItem = true;
                $cartItem = $item;
                break;
            }
        }

        $productPrice = (float) $product->getPrice();

        if ($isExistingItem && $cartItem !== null) {
            // Update existing item
            $newQuantity = $cartItem->getQuantity() + $quantity;
            $cartItem->setQuantity($newQuantity);
            // Math handled automatically now by the dynamic getter
        } else {
            // Create new cart item
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cartItem->setPrice($product->getPrice());

            $this->em->persist($cartItem);

            if (method_exists($cart, 'addCartItem')) {
                $cart->addCartItem($cartItem);
            } else {
                $cart->getCartItems()->add($cartItem);
            }
        }

        $this->recalculateCartTotals($cart);
        $this->em->persist($cart);
        $this->em->flush();
    }

    public function removeItem(CartItem $cartItem): void
    {
        $cart = $cartItem->getCart();

        if ($cart) {
            if (method_exists($cart, 'removeCartItem')) {
                $cart->removeCartItem($cartItem);
            } else {
                $cart->getCartItems()->removeElement($cartItem);
            }
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

    public function clearCart(): void
    {
        $cart = $this->getCart();

        if (!$cart) {
            return;
        }

        foreach ($cart->getCartItems() as $item) {
            $this->em->remove($item);
        }

        $cart->getCartItems()->clear();

        $cart->setTotalQuantity(0);
        $cart->setTotalPrice(number_format(0, 2, '.', ''));

        $this->em->persist($cart);
        $this->em->flush();

        $this->session->remove('cartId');
    }

    private function recalculateCartTotals(Cart $cart): void
    {
        $totalQuantity = 0;
        $totalPrice = 0.00;

        foreach ($cart->getCartItems() as $item) {
            // --- THE FIX ---
            // Force the database subtotal column to sync with the dynamic math
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
