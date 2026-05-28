<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationPublisher
{
    public function __construct(
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $router,
        private SocketIoPublisher $socketIoPublisher,
        private Messaging $messaging,
        private LoggerInterface $logger,
    ) {}

    public function send(User $recipient, string $title, string $message, string $routeName, array $routeParams = [], string $type = 'system', bool $flush = true): void
    {
        $targetUrl = $this->router->generate($routeName, $routeParams);

        $notification = new Notification();
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setTargetUrl($targetUrl);
        $notification->setIsRead(false);
        $notification->setType($type);
        // createdAt is handled by the constructor in your entity!

        // 1. Universal Recipient Mapping
        $notification->setRecipient($recipient);

        $this->em->persist($notification);

        if ($flush) {
            $this->em->flush();
        }

        // 2. Real-time notification via Socket.io
        $payload = [
            'title' => $title,
            'message' => $message,
            'targetUrl' => $targetUrl,
            'type' => $type,
        ];

        if ($type === 'order' && isset($routeParams['id'])) {
            $payload['orderId'] = (string) $routeParams['id'];
        }

        $this->socketIoPublisher->publish($recipient->getId(), 'notification', $payload);

        // 3. Tell dashboard clients to refresh lists/stats without manual reload
        $this->socketIoPublisher->publish($recipient->getId(), 'dashboard_refresh', [
            'entity' => $type,
            'action' => $this->inferActionFromTitle($title),
            'message' => $message,
            'targetUrl' => $targetUrl,
        ]);

        if ($type === 'order' && str_contains(strtolower($title), 'created')) {
            $this->socketIoPublisher->publish($recipient->getId(), 'new_order', [
                'orderId' => $routeParams['id'] ?? null,
                'message' => $message,
            ]);
        }

        // 4. Push notification to mobile device (if token is available)
        $deviceToken = $recipient->getDeviceToken();
        if ($deviceToken) {
            $pushData = [
                'type' => $type,
                'targetUrl' => (string) $targetUrl,
            ];

            if ($type === 'order' && isset($routeParams['id'])) {
                $pushData['orderId'] = (string) $routeParams['id'];
            }

            $pushMessage = CloudMessage::new()
                ->toToken($deviceToken)
                ->withNotification(FirebaseNotification::create($title, $message))
                ->withData($pushData);

            try {
                $this->messaging->send($pushMessage);
            } catch (\Throwable $e) {
                $this->logger->warning('Push notification send failed: {message}', [
                    'message' => $e->getMessage(),
                    'recipientId' => $recipient->getId(),
                    'title' => $title,
                    'type' => $type,
                ]);
            }
        }
    }

    private function inferActionFromTitle(string $title): string
    {
        $normalized = strtolower($title);

        if (str_contains($normalized, 'created')) {
            return 'created';
        }
        if (str_contains($normalized, 'updated')) {
            return 'updated';
        }
        if (str_contains($normalized, 'removed') || str_contains($normalized, 'deleted')) {
            return 'deleted';
        }

        return 'changed';
    }
}
