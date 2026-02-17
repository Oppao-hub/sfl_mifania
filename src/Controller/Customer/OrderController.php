<?php

namespace App\Controller\Customer;

use App\Repository\ProductRepository;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use App\Service\CartService;
use App\Repository\UserRepository;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('customer/account/orders.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductRepository $productRepo): Response
    {
        // 1. Get the current gender from Session (Default to 'Women' if not set)
        $currentGender = $request->getSession()->get('shop_gender', 'Women');

        // 2. Fetch the Products (Filtered by Gender)
        $products = $productRepo->findByGender($currentGender);

        // 3. Send to your Template
        return $this->render('home/index.html.twig', [
            'active_gender' => $currentGender,
            'products' => $products,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('customer/show_order.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/place', name: 'app_place_order', methods: ['POST', 'GET'])]
    public function placeOrder(
        CartService $cartService,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {

        $cart = $cartService->getCart();
        $cartItems = $cart->getCartItems();

        if ($cartItems->isEmpty()) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart_show');
        }

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to place an order.');
            return $this->redirectToRoute('app_login');
        }

        // 1. Create the Order
        $order = new Order();
        /** @var \App\Entity\User $user */
        $order->setCustomer($user->getCustomer());
        $totalAmount = 0;
        $order->setTotalAmount('0');

        $totalRewardPoints = 0;

        // 2. Create Order Items
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->getProduct();
            $quantity = $cartItem->getQuantity();
            $price = $product->getPrice();
            $subtotal = (float) $cartItem->getSubtotal();

            $item = new OrderItem();
            $item->setOrder($order);
            $item->setProduct($product);
            $item->setQuantity($quantity);
            $item->setPrice((string) $price);
            $item->setSubtotal((string) $subtotal);

            // Reduce product stock
            $product->deductStockQuantity($quantity);

            $order->addOrderItem($item);
            $entityManager->persist($item);

            $totalAmount += (float) $subtotal;
        }

        $order->setTotalAmount((string) $totalAmount);
        $order->setRewardPointsEarned($totalRewardPoints);

        $entityManager->persist($order);
        $entityManager->flush();

        // Clear cart after successful order
        $cartService->clearCart();

        $admin = $userRepository->findAdmin();
        if ($admin) {
            $adminProfile = $admin->getAdmin();
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

        return $this->redirectToRoute('app_order_index');
    }
}
