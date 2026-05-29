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
class OrderDeleteController extends AbstractController
{
    #[Route('/{id}', name: 'api_order_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(
        Order $order,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$user || !$user->getCustomer()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if ($order->getCustomer()?->getId() !== $user->getCustomer()->getId()) {
            throw new AccessDeniedHttpException('You cannot delete this order.');
        }

        if ($order->getOrderStatus() !== OrderStatus::CANCELLED) {
            throw new BadRequestHttpException('Only cancelled orders can be deleted.');
        }

        $em->remove($order);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Order deleted successfully.',
        ]);
    }
}
