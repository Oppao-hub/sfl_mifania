<?php

namespace App\Controller\Dashboard;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard/notification')]
final class NotificationController extends AbstractController
{
    #[Route('/fetch', name: 'app_dashboard_fetch_notification')]
    public function fetch(NotificationRepository $notificationRepo): Response
    {
        $user = $this->getUser();

        $notifications = $notificationRepo->findBy(
            ['recipient' => $user],
            ['createdAt' => 'DESC'],
            5
        );

        $unreadCount = $notificationRepo->count([
            'recipient' => $user,
            'isRead' => false
        ]);

        return $this->render('dashboard/notification/index.html.twig', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);

    }
}
