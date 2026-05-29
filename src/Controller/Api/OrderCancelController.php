<?php

namespace App\Controller\Api;

use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/orders')]
class OrderCancelController extends AbstractController
{
    #[Route('/{id}/cancel', name: 'api_order_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(
        Order $order,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$user || !$user->getCustomer()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if ($order->getCustomer()?->getId() !== $user->getCustomer()->getId()) {
            throw new AccessDeniedHttpException('You cannot cancel this order.');
        }

        $status = $order->getOrderStatus();
        if ($status === OrderStatus::CANCELLED) {
            return $this->json([
                'success' => true,
                'message' => 'Order is already cancelled.',
                'orderStatus' => $status->value,
            ]);
        }

        if (!in_array($status, [OrderStatus::PENDING, OrderStatus::PROCESSING], true)) {
            throw new BadRequestHttpException('This order can no longer be cancelled.');
        }

        $order->setOrderStatus(OrderStatus::CANCELLED);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
            'orderStatus' => OrderStatus::CANCELLED->value,
        ]);
    }
}
