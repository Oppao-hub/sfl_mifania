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

    /**
     * Add item to cart or increase quantity when same product+size+color exists.
     *
     * @param Product $product
     * @param int $quantity
     * @param Size|null $size
     * @param Color|null $color
     * @return void
     */
    public function addItem(Product $product, int $quantity = 1): void
    {
        $cart = $this->getCart();
        $isExistingItem = false;
        $cartItem = null;

        // Try to find an existing matching cart item
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

            $newSubtotal = $productPrice * $newQuantity;
            $cartItem->setSubtotal(number_format($newSubtotal, 2, '.', ''));

            // CartItem is managed by Doctrine, so no need to persist explicitly
        } else {
            // Create new cart item
            $cartItem = new CartItem();
            $subtotal = $productPrice * $quantity;

            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cartItem->setPrice($product->getPrice());
            $cartItem->setSubtotal(number_format($subtotal, 2, '.', ''));

            $this->em->persist($cartItem);
            // also add to the cart collection if necessary (helps in-memory)
            if (method_exists($cart, 'addCartItem')) {
                $cart->addCartItem($cartItem);
            } else {
                // fallback: if it's a Collection, add directly
                $cart->getCartItems()->add($cartItem);
            }
        }

        // Recalculate totals after modifications and persist cart state
        $this->recalculateCartTotals($cart);

        // Persist cart (in case totals or relationship changed) and flush once
        $this->em->persist($cart);
        $this->em->flush();
    }

    public function removeItem(CartItem $cartItem): void
    {
        $cart = $cartItem->getCart(); // Get the cart before removing the item

        // Ensure the item is removed both from DB and from the cart collection
        if ($cart) {
            if (method_exists($cart, 'removeCartItem')) {
                $cart->removeCartItem($cartItem);
            } else {
                $cart->getCartItems()->removeElement($cartItem);
            }
        }

        $this->em->remove($cartItem);

        // Recalculate and flush
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
            // Nothing to clear
            return;
        }

        // Remove each cart item
        foreach ($cart->getCartItems() as $item) {
            $this->em->remove($item);
        }

        // Clear the collection to reflect changes in memory
        $cart->getCartItems()->clear();

        // Reset totals
        $cart->setTotalQuantity(0);
        $cart->setTotalPrice(number_format(0, 2, '.', ''));

        // Persist changes and flush once
        $this->em->persist($cart);
        $this->em->flush();

        // Remove cart id from session to force creation of a fresh cart next time
        $this->session->remove('cartId');
    }

    private function recalculateCartTotals(Cart $cart): void
    {
        $totalQuantity = 0;
        $totalPrice = 0.00;

        foreach ($cart->getCartItems() as $item) {
            $totalQuantity += $item->getQuantity();
            // Ensure subtotal is treated as a float for summation
            $totalPrice += (float) $item->getSubtotal();
        }

        $cart->setTotalQuantity($totalQuantity);
        // Store the total price back as a formatted string to match the entity's decimal type
        $cart->setTotalPrice(number_format($totalPrice, 2, '.', ''));
        // Do NOT flush here; caller is responsible for persisting/flushing
    }
}
