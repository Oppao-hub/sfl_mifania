<?Php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CartService
{
    private EntityManagerInterface $em;
    private $session;
    private Security $security;

    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, Security $security)
    {
        $this->em = $em;
        $this->session = $requestStack->getSession();
        $this->security = $security;
    }

    public function getCart(): Cart
    {
        $cartId = $this->session->get('cartId');
        $cart = $cartId ? $this->em->getRepository(Cart::class)->find($cartId) : null;

        if (!$cart) {
            $cart = new Cart();

            // Associate cart with current user's customer if logged in
            $user = $this->security->getUser();
            if ($user && $user instanceof \App\Entity\User && $user->getCustomer()) {
                $cart->setCustomer($user->getCustomer());
            }

            $this->em->persist($cart);
            $this->em->flush();
            $this->session->set('cartId', $cart->getId());
        }

        return $cart;
    }

    public function addItem(Product $product, int $quantity = 1)
    {
        $cart = $this->getCart();

        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct() == $product) {
                $item->setQuantity($item->getQuantity() + $quantity);
                $this->em->flush();
                return;
            }
        }

        $cartItem = new CartItem();
        $cartItem->setCart($cart);
        $cartItem->setProduct($product);
        $cartItem->setQuantity($quantity);
        $cartItem->setPrice($product->getPrice());
        $cartItem->setSubtotal($product->getPrice() * $quantity);

        $this->em->persist($cartItem);
        $this->em->flush();
    }

    public function removeItem(CartItem $cartItem)
    {
        $this->em->remove($cartItem);
        $this->em->flush();
    }

    public function getTotal(): float
    {
        $cart = $this->getCart();
        $total = 0;
        foreach ($cart->getCartItems() as $item) {
            $total += $item->getSubtotal();
        }
        return $total;
    }

    public function clearCart(): void
    {
        $cartId = $this->session->get('cartId');
        if ($cartId) {
            $cart = $this->em->getRepository(Cart::class)->find($cartId);
            if ($cart) {
                // Remove all cart items
                foreach ($cart->getCartItems() as $item) {
                    $this->em->remove($item);
                }
                $this->em->remove($cart);
                $this->em->flush();
            }
            $this->session->remove('cartId');
        }
    }
}
