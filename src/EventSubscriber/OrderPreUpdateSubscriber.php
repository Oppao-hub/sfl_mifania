<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Service\OrderChangeBuffer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/** Records order field changes in preUpdate; processing happens in OrderPostFlushSubscriber. */
final class OrderPreUpdateSubscriber
{
    public function __construct(
        private OrderChangeBuffer $changeBuffer,
    ) {}

    #[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Order::class)]
    public function preUpdate(Order $order, PreUpdateEventArgs $event): void
    {
        $orderId = $order->getId();
        if ($orderId === null) {
            return;
        }

        $this->changeBuffer->record($orderId, $event->getEntityChangeSet());
    }
}
