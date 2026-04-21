<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_recent_notifications', [$this, 'getRecentNotifications']),
            new TwigFunction('get_unread_notifications_count', [$this, 'getUnreadCount']),
        ];
    }

    public function getRecentNotifications(int $limit = 5): array
    {
        $user = $this->security->getUser();
        
        // Safety: If for some reason we have a role entity, get the base User
        if (method_exists($user, 'getUser')) {
            $user = $user->getUser();
        }

        if (!$user instanceof User || !$user->getId()) {
            return [];
        }

        return $this->notificationRepository->findRecent($user, $limit);
    }

    public function getUnreadCount(): int
    {
        $user = $this->security->getUser();

        if (method_exists($user, 'getUser')) {
            $user = $user->getUser();
        }

        if (!$user instanceof User || !$user->getId()) {
            return 0;
        }

        return $this->notificationRepository->countUnread($user);
    }
}
