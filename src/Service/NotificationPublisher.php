<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationPublisher
{
    public function __construct(
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $router,
        private SocketIoPublisher $socketIoPublisher
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
        $this->socketIoPublisher->publish($recipient->getId(), 'notification', [
            'title' => $title,
            'message' => $message,
            'targetUrl' => $targetUrl,
            'type' => $type
        ]);
    }
}
