<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationPublisher
{
    public function __construct(
        private HubInterface $hub,
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $router
    ) {}

    public function send(User $recipient, string $title, string $message, string $routeName, array $routeParams = [], string $type = 'system'): void
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
        $this->em->flush();

        // 2. Universal Mercure Topic (One channel for everyone based on User ID)
        $topic = sprintf('/notifications/user/%s', $recipient->getId());

        $payload = json_encode([
            'title' => $title,
            'message' => $message,
            'targetUrl' => $targetUrl,
            'type' => $type,
        ]);

        $this->hub->publish(new Update($topic, $payload));
    }
}
