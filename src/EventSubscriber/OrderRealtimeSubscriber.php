<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Service\RealtimeSync;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

/**
 * Broadcasts order changes from any source (mobile API, dashboard, checkout) to staff dashboards.
 */
#[AsEntityListener(event: Events::postUpdate, entity: Order::class)]
#[AsEntityListener(event: Events::preRemove, entity: Order::class)]
class OrderRealtimeSubscriber
{
    public function __construct(
        private RealtimeSync $realtimeSync,
    ) {}

    public function postUpdate(Order $order, PostUpdateEventArgs $event): void
    {
        $changeSet = $event->getEntityChangeSet();
        $relevantFields = ['orderStatus', 'paymentStatus', 'paymentMethod', 'totalAmount'];
        $hasRelevantChange = false;

        foreach ($relevantFields as $field) {
            if (array_key_exists($field, $changeSet)) {
                $hasRelevantChange = true;
                break;
            }
        }

        if (!$hasRelevantChange) {
            return;
        }

        $statusLabel = $order->getOrderStatus()?->value ?? 'updated';
        $action = $statusLabel === 'Cancelled' ? 'cancelled' : 'updated';

        $this->realtimeSync->publishOrderChange($order, $action, $statusLabel);
    }

    public function preRemove(Order $order, PreRemoveEventArgs $event): void
    {
        $orderId = $order->getId();
        if ($orderId === null) {
            return;
        }

        $customerUserId = $order->getCustomer()?->getUser()?->getId();
        $this->realtimeSync->publishOrderRemoved($orderId, $customerUserId);
    }
}
