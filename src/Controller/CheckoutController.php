<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Entity\OrderItem;
use App\Entity\Enum\OrderStatus;
use App\Entity\Enum\PaymentStatus;
use App\Form\CheckoutType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $customer = $user->getCustomer();

        // Note: Adjust this logic based on how your Cart entity works.
        // Assuming the customer has one active cart containing their items.
        $cart = $customer->getCarts()->first();

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart_index'); // Redirect back to cart if empty
        }

        // 1. Create the Form
        // We can pre-fill the form with the customer's saved address
        $defaultData = [
            'shippingAddress' => $customer->getAddress(),
            'city' => $customer->getCity(),
            'postalCode' => $customer->getPostalCode(),
        ];

        $form = $this->createForm(CheckoutType::class, $defaultData);
        $form->handleRequest($request);

        // 2. Handle Form Submission
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Create a new Order
            $order = new Order();
            $order->setCustomer($customer);
            // Assuming you have an Enum or String for these
            $order->setOrderStatus(OrderStatus::PENDING);
            $order->setPaymentMethod($data['paymentMethod']);
            $order->setPaymentStatus(PaymentStatus::PENDING);
            $order->setCreatedAt(new \DateTimeImmutable());

            $totalAmount = 0;

            // Loop through Cart Items and move them to Order Items
            foreach ($cart->getCartItems() as $cartItem) {
                // Assuming you have an OrderItem entity to store what they bought
                /* $orderItem = new OrderItem();
                $orderItem->setProduct($cartItem->getProduct());
                $orderItem->setQuantity($cartItem->getQuantity());
                $orderItem->setPrice($cartItem->getProduct()->getPrice());
                $order->addOrderItem($orderItem);
                */

                $totalAmount += ($cartItem->getProduct()->getPrice() * $cartItem->getQuantity());

                // Remove item from cart
                $em->remove($cartItem);
            }

            $order->setTotalAmount($totalAmount);

            // Save the order
            $em->persist($order);
            $em->flush();

            $this->addFlash('success', 'Order placed successfully!');
            return $this->redirectToRoute('app_order_success', ['id' => $order->getId()]);
        }

        return $this->render('checkout/index.html.twig', [
            'checkoutForm' => $form->createView(),
            'cart' => $cart,
        ]);
    }

    #[Route('/checkout/success/{id}', name: 'app_order_success')]
    public function success(Order $order): Response
    {
        // Make sure the user can only see their own order success page
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($order->getCustomer() !== $user->getCustomer()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }
}
