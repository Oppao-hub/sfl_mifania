<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound as MessagingNotFound;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
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

    public function send(User $recipient, string $title, string $message, string $routeName, array $routeParams = [], string $type = 'system', bool $flush = true, ?string $orderReference = null, ?int $orderIdOverride = null): void
    {
        try {
            $targetUrl = $this->router->generate($routeName, $routeParams);
        } catch (\Throwable $exception) {
            $this->logger->warning('Notification route generation failed, using dashboard fallback.', [
                'routeName' => $routeName,
                'routeParams' => $routeParams,
                'message' => $exception->getMessage(),
            ]);
            $targetUrl = $this->router->generate('app_dashboard');
        }

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

        // Real-time in-app notification (data refresh is handled by RealtimeSync)
        $payload = [
            'title' => $title,
            'message' => $message,
            'targetUrl' => $targetUrl,
            'type' => $type,
        ];

        if ($notification->getId() !== null) {
            $payload['notificationId'] = (string) $notification->getId();
        }

        $orderId = $orderIdOverride ?? ($routeParams['id'] ?? null);
        if ($type === 'order' && $orderId !== null) {
            $payload['orderId'] = (string) $orderId;
            if ($orderReference) {
                $payload['orderReference'] = $orderReference;
            }
        }

        $this->socketIoPublisher->publish($recipient->getId(), 'notification', $payload);

        if ($type === 'order' && $orderId !== null) {
            $this->socketIoPublisher->publish($recipient->getId(), 'dashboard_refresh', [
                'entity' => 'order',
                'action' => str_contains(strtolower($title), 'cancel') ? 'cancelled' : 'updated',
                'orderId' => (string) $orderId,
                'message' => $message,
                'targetUrl' => $targetUrl,
            ]);

            $this->socketIoPublisher->publish($recipient->getId(), 'order_status_update', [
                'orderId' => (string) $orderId,
                'status' => $this->inferStatusFromMessage($message),
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'orderReference' => $orderReference,
            ]);
        }

        // Push notification to mobile device (if token is available)
        $deviceToken = $recipient->getDeviceToken();
        if ($deviceToken) {
            $pushData = [
                'type' => $type,
                'targetUrl' => (string) $targetUrl,
                'title' => $title,
                'message' => $message,
            ];

            if ($type === 'order' && $orderId !== null) {
                $pushData['orderId'] = (string) $orderId;
                if ($orderReference) {
                    $pushData['orderReference'] = $orderReference;
                }
            }

            $pushMessage = CloudMessage::new()
                ->toToken($deviceToken)
                ->withNotification(FirebaseNotification::create($title, $message))
                ->withData($pushData)
                ->withAndroidConfig(AndroidConfig::fromArray([
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'default',
                        'sound' => 'default',
                    ],
                ]))
                ->withApnsConfig(ApnsConfig::fromArray([
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $message,
                            ],
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ]));

            try {
                $this->messaging->send($pushMessage);
            } catch (MessagingNotFound $e) {
                $recipient->setDeviceToken(null);
                $this->em->flush();
                $this->logger->warning('Removed stale push token after delivery failure.', [
                    'recipientId' => $recipient->getId(),
                    'title' => $title,
                    'type' => $type,
                ]);
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
}
