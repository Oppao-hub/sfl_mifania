<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Service\NotificationPublisher;
use App\Service\OrderMailerService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use BackedEnum;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postUpdate, entity: Order::class)]
class OrderStatusSubscriber
{
    public function __construct(
        private NotificationPublisher $notificationPublisher,
        private OrderMailerService $orderMailerService,
        private LoggerInterface $logger,
    ) {}

    public function postUpdate(Order $order, PostUpdateEventArgs $event): void
    {
        try {
            $this->notifyCustomerOfChanges($order, $event);
        } catch (\Throwable $exception) {
            $this->logger->error('Order status notification failed.', [
                'orderId' => $order->getId(),
                'exception' => $exception,
            ]);
        }
    }

    private function notifyCustomerOfChanges(Order $order, PostUpdateEventArgs $event): void
    {
        $customer = $order->getCustomer();
        $user = $customer ? $customer->getUser() : null;

        if (!$user) {
            return;
        }

        $changeSet = $event->getEntityChangeSet();

        // 1. Handle Order Status Change
        if (array_key_exists('orderStatus', $changeSet)) {
            $newStatus = $changeSet['orderStatus'][1] ?? $order->getOrderStatus();
            $statusLabel = $this->normalizeStatusLabel($newStatus, 'Processing');
            $body = "Your order #{$order->getId()} is now {$statusLabel}.";

            $this->notificationPublisher->send(
                $user,
                'Order Status Updated',
                $body,
                'app_account_order_view',
                ['id' => $order->getId()],
                'order',
                false
            );

            try {
                $this->orderMailerService->sendStatusUpdateEmail($order);
            } catch (\Throwable $exception) {
                $this->logger->warning('Order status email failed.', [
                    'orderId' => $order->getId(),
                    'exception' => $exception,
                ]);
            }
        }

        // 2. Handle Payment Status Change
        if (array_key_exists('paymentStatus', $changeSet)) {
            $newStatus = $changeSet['paymentStatus'][1] ?? $order->getPaymentStatus();
            $statusLabel = $this->normalizeStatusLabel($newStatus, 'Pending');
            $body = "The payment for order #{$order->getId()} is now {$statusLabel}.";

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
