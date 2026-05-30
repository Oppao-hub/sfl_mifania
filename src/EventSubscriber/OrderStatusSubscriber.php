<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Service\OrderCustomerNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postUpdate, entity: Order::class)]
class OrderStatusSubscriber
{
    public function __construct(
        private OrderCustomerNotificationService $orderCustomerNotificationService,
        private LoggerInterface $logger,
    ) {}

    public function postUpdate(Order $order, PostUpdateEventArgs $event): void
    {
        try {
            $this->orderCustomerNotificationService->notifyFromChangeBuffer($order);
        } catch (\Throwable $exception) {
            $this->logger->error('Order status notification failed.', [
                'orderId' => $order->getId(),
                'exception' => $exception,
            ]);
        }
    }
}
