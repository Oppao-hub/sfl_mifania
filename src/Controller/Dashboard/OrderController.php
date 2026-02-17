<?php

namespace App\Controller\Dashboard;

use App\Entity\Order;
use App\Entity\Enum\OrderStatus;
use App\Entity\Enum\PaymentMethod;
use App\Entity\Enum\PaymentStatus;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/orders')]
final class OrderController extends AbstractController
{
    #[Route('/', name: 'app_dashboard_order_index', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $orderStatus = $request->query->get('orderStatus');

        if ($orderStatus) {
            $orders = $orderRepository->findBy(['orderStatus' => $orderStatus]);
        } else {
            $orders = $orderRepository->findAll();
        }

        return $this->render('dashboard/order/index.html.twig', [
            'orders' => $orders,
            'orderStatus' => $orderStatus,
            'statuses' => OrderStatus::cases(),
            'paymentMethods' => PaymentMethod::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
        ]);
    }

    #[Route('/new', name: 'app_dashboard_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Order created successfully!');
            return $this->redirectToRoute('app_dashboard_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('dashboard/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dashboard_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Order updated successfully!');
            return $this->redirectToRoute('app_dashboard_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Order deleted successfully!');
        return $this->redirectToRoute('app_dashboard_order_index', [], Response::HTTP_SEE_OTHER);
    }

   // Add this to handle Payment Method changes
    #[Route('/{id}/change-payment-method', 'app_dashboard_order_change_payment_method', methods: ['POST'])]
    public function changePaymentMethod(Order $order, EntityManagerInterface $entityManager, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('change_payment_method' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_dashboard_order_index');
        }

        $methodValue = $request->request->get('payment_method');
        $newMethod = PaymentMethod::tryFrom($methodValue);

        if ($newMethod) {
            $order->setPaymentMethod($newMethod);
            $entityManager->flush();
            $this->addFlash('success', 'Payment method updated to ' . $newMethod->value);
        }

        return $this->redirectToRoute('app_dashboard_order_index');
    }

    // Update your existing Change Payment Status to include CSRF check
    #[Route('/{id}/change-order-payment-status', 'app_dashboard_order_change_payment_status', methods: ['POST'])]
    public function changePaymentStatus(Order $order, EntityManagerInterface $entityManager, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('change_payment_status' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_dashboard_order_index');
        }

        $newStatusValue = $request->request->get('payment_status');
        $newStatus = PaymentStatus::tryFrom($newStatusValue);

        if ($newStatus) {
            $order->setPaymentStatus($newStatus);
            $entityManager->flush();
            $this->addFlash('success', 'Payment status updated to ' . $newStatus->value);
        }

        return $this->redirectToRoute('app_dashboard_order_index');
    }

    // Update your existing Change Order Status to include CSRF check
    #[Route('/{id}/change-order-status', 'app_dashboard_order_change_status', methods: ['POST'])]
    public function changeOrderStatus(Order $order, EntityManagerInterface $entityManager, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('change_status' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_dashboard_order_index');
        }

        $newStatusValue = $request->request->get('status');
        $newStatus = OrderStatus::tryFrom($newStatusValue);

        if ($newStatus) {
            $order->setOrderStatus($newStatus);
            $entityManager->flush();
            $this->addFlash('success', 'Order status updated to ' . $newStatus->value);
        }

        return $this->redirectToRoute('app_dashboard_order_index');
    }
}
