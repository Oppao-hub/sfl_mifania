<?php

namespace App\Controller\Frontend;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Entity\Enum\OrderStatus;
use App\Entity\Enum\PaymentStatus;
use App\Form\CheckoutType;
use App\Service\CartService;
use App\Service\RewardManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function index(Request $request, EntityManagerInterface $em, CartService $cartService, RewardManager $rewardManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $customer = $user->getCustomer();

        // FIX 1: Use your beautiful new CartService!
        $cart = $cartService->getCart();

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart_show');
        }

        // Create the Form with default data
        $defaultData = [];
        if ($customer) {
            $defaultData = [
                'shippingAddress' => $customer->getAddress(),
                'city' => $customer->getCity(),
                'postalCode' => $customer->getPostalCode(),
            ];
        }

        $form = $this->createForm(CheckoutType::class, $defaultData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $order = new Order();
            $order->setCustomer($customer);
            $order->setOrderStatus(OrderStatus::PENDING);
            $order->setPaymentMethod($data['paymentMethod']);
            $order->setPaymentStatus(PaymentStatus::PENDING);
            $order->setTotalAmount($cart->getTotalPrice()); // Use the pre-calculated cart total!

            // FIX 2: Actually create the Order Items!
            foreach ($cart->getCartItems() as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->setProduct($cartItem->getProduct());
                $orderItem->setQuantity($cartItem->getQuantity());
                $orderItem->setPrice($cartItem->getPrice());
                $orderItem->setSubtotal($cartItem->getSubtotal());

                // Add it to the Order
                $order->addOrderItem($orderItem);

                // Persist the order item
                $em->persist($orderItem);
            }

            $em->persist($order);
            $em->flush(); // Flush now to trigger PrePersist and generate points

            if ($customer && $order->getRewardPoints() > 0) {
                // The service safely handles the Wallet math and the Transaction ledger!
                $rewardManager->earnPointsFromOrder($customer, $order, $order->getRewardPoints());
            }

            $cartService->clearCart();

            $this->addFlash('success', 'Order placed successfully!');
            return $this->redirectToRoute('app_order_success', ['id' => $order->getId()]);
        }

        $status = ($form->isSubmitted() && !$form->isValid()) ? 422 : 200;

        return $this->render('frontend/checkout/index.html.twig', [
            'checkoutForm' => $form->createView(),
            'cart' => $cart,
        ], new Response(null, $status));
    }

    #[Route('/checkout/success/{id}', name: 'app_order_success')]
    public function success(Order $order): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($order->getCustomer() !== $user->getCustomer()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('frontend/checkout/success.html.twig', [
            'order' => $order,
        ]);
    }
}
