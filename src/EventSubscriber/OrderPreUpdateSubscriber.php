<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Service\OrderChangeBuffer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Records order field changes during preUpdate for postUpdate listeners.
 * Doctrine ORM 3.x PostUpdateEventArgs no longer exposes the change set.
 */
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

    #[AsDoctrineListener(event: Events::postFlush)]
    public function onPostFlush(): void
    {
        $this->changeBuffer->flush();
    }
}
