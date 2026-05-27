<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Service\NotificationPublisher;
use App\Service\OrderMailerService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::preUpdate, entity: Order::class)]
class OrderStatusSubscriber
{
    public function __construct(
        private NotificationPublisher $notificationPublisher,
        private OrderMailerService $orderMailerService,
        private Messaging $messaging, // 💡 Added Firebase Messaging
        private LoggerInterface $logger // 💡 Added Logger to catch errors safely
    ) {}

    public function preUpdate(Order $order, PreUpdateEventArgs $event): void
    {
        $customer = $order->getCustomer();
        $user = $customer ? $customer->getUser() : null;

        if (!$user) {
            return;
        }

        $title = null;
        $body = null;

        // 1. Handle Order Status Change
        if ($event->hasChangedField('orderStatus')) {
            $newStatus = $event->getNewValue('orderStatus');
            $title = 'Order Status Updated 📦';
            $body = "Your order #{$order->getId()} is now {$newStatus->value}.";

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
            $title = 'Payment Update 💳';
            $body = "The payment for order #{$order->getId()} is now {$newStatus->value}.";

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

        // 3. Send the Firebase Push Notification to the phone
        if ($title && $body && $user->getDeviceToken()) {
            $message = CloudMessage::withTarget('token', $user->getDeviceToken())
                ->withNotification(FirebaseNotification::create($title, $body));

            try {
                $this->messaging->send($message);
                $this->logger->info("Push notification sent to User ID: " . $user->getId());
            } catch (\Exception $e) {
                // Catching errors ensures a failed notification doesn't crash the whole save process
                $this->logger->error("Failed to send push notification: " . $e->getMessage());
            }
        }
    }
}
