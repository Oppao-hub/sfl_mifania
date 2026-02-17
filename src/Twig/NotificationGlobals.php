<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NotificationGlobals extends AbstractExtension implements GlobalsInterface
{
    private NotificationRepository $notificationRepository;
    private Security $security;

    public function __construct(NotificationRepository $notificationRepository, Security $security)
    {
        $this->notificationRepository = $notificationRepository;
        $this->security = $security;
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            return [
                'unreadCount' => 0,
                'recentNotifications' => [],
            ];
        }

        return [
            'unreadCount' => $this->notificationRepository->countUnread($user),
            'recentNotifications' => $this->notificationRepository->findRecent($user),
        ];
    }
}
