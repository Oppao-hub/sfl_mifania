<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Service\NotificationPublisher;
use App\Service\OrderMailerService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use BackedEnum;

#[AsEntityListener(event: Events::preUpdate, entity: Order::class)]
class OrderStatusSubscriber
{
    public function __construct(
        private NotificationPublisher $notificationPublisher,
        private OrderMailerService $orderMailerService,
    ) {}

    public function preUpdate(Order $order, PreUpdateEventArgs $event): void
    {
        $customer = $order->getCustomer();
        $user = $customer ? $customer->getUser() : null;

        if (!$user) {
            return;
        }

        // 1. Handle Order Status Change
        if ($event->hasChangedField('orderStatus')) {
            $newStatus = $event->getNewValue('orderStatus');
            $statusLabel = $this->normalizeStatusLabel($newStatus, 'Processing');
            $body = "Your order #{$order->getId()} is now {$statusLabel}.";

            // Keep your existing in-app and email notifications
            $this->notificationPublisher->send(
                $user,
                'Order Status Updated',
                $body,
                'app_account_order_view',
                ['id' => $order->getId()],
                'order',
                false
            );
            $this->orderMailerService->sendStatusUpdateEmail($order);
        }

        // 2. Handle Payment Status Change
        if ($event->hasChangedField('paymentStatus')) {
            $newStatus = $event->getNewValue('paymentStatus');
            $statusLabel = $this->normalizeStatusLabel($newStatus, 'Pending');
            $body = "The payment for order #{$order->getId()} is now {$statusLabel}.";

            // Add an in-app notification for payment as well
            $this->notificationPublisher->send(
                $user,
                'Payment Status Updated',
                $body,
                'app_account_order_view',
                ['id' => $order->getId()],
                'order',
                false
            );
        }
    }

    private function normalizeStatusLabel(mixed $status, string $fallback): string
    {
        if ($status instanceof BackedEnum) {
            return (string) $status->value;
        }

        if (is_string($status) && trim($status) !== '') {
            return $status;
        }

        return $fallback;
    }
}
