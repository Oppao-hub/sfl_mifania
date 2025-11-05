<?php

namespace App\Controller\Customer;

use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkout')]
#[IsGranted('ROLE_USER')]
final class CheckoutController extends AbstractController
{
    #[Route('', name: 'app_checkout')]
    public function checkout(
        CartService $cartService,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ): Response {
        $cart = $cartService->getCart();
        $cartItems = $cart->getCartItems();

        if ($cartItems->isEmpty()) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart_show');
        }

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to checkout.');
            return $this->redirectToRoute('app_login'); // Redirect to login if user is not authenticated
        }


        // 1. Create new Order
        $order = new Order();
        /** @var \App\Entity\User $user */
        $order->setCustomer($user->getCustomer());
        $totalAmount = 0;
        $order->setTotalAmount('0');

        // 2. Create OrderItems
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->getProduct();
            $quantity = $cartItem->getQuantity();
            $price = $product->getPrice();
            $subtotal = $cartItem->getSubtotal();

            $item = new OrderItem();
            $item->setCustomerOrder($order);
            $item->setProduct($product);
            $item->setQuantity($quantity);
            $item->setPrice((string) $price); // price at time of purchase - convert to string
            $item->setSubtotal((string) $subtotal); // convert to string

            $product->deductStockQuantity($quantity);

            $order->addOrderItem($item);
            $entityManager->persist($item); // Persist each OrderItem individually

            $totalAmount += (float) $subtotal; // Convert to float for calculation
        }

        $order->setTotalAmount((string) $totalAmount);

        $entityManager->persist($order);
        $entityManager->flush();

        // 3. Clear cart
        $cartService->clearCart();

        // Find the admin user using the custom repository method
        $admin = $userRepository->findAdmin();
        if ($admin) {
            // Get the Admin profile associated with the User
            $adminProfile = $admin->getAdmin(); // This is correct
        }

        if (isset($adminProfile)) {
            $notification = new Notification();
            $notification->setTitle('New Order');
            $notification->setMessage('A new order has been placed.');
            $notification->setAdmin($adminProfile);
            $notification->setIsRead(false);
            $notification->setRecipient($admin);

            $entityManager->persist($notification);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Order placed successfully!');

        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }
}
