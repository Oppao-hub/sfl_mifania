<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Service\NotificationPublisher;
use App\Service\OrderMailerService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, entity: Order::class)]
class OrderStatusSubscriber
{
    public function __construct(
        private NotificationPublisher $notificationPublisher,
        private OrderMailerService $orderMailerService
    ) {}

    public function preUpdate(Order $order, PreUpdateEventArgs $event): void
    {
        if ($event->hasChangedField('orderStatus')) {
            $newStatus = $event->getNewValue('orderStatus');
            $customer = $order->getCustomer();

            if ($customer && $customer->getUser()) {
                // 1. Send In-App Notification
                $this->notificationPublisher->send(
                    $customer->getUser(),
                    'Order Status Updated',
                    "Your order #{$order->getId()} is now {$newStatus->value}.",
                    'app_account_order_view',
                    ['id' => $order->getId()],
                    'order',
                    false // Don't flush yet, we are in preUpdate
                );

                // 2. Send Email Notification
                $this->orderMailerService->sendStatusUpdateEmail($order);
            }
        }
    }
}
