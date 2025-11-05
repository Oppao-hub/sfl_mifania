<?php

namespace App\Controller\Admin;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/notification')]
#[IsGranted('ROLE_ADMIN')]
final class NotificationController extends AbstractController
{
    #[Route('/fetch', name: 'app_admin_fetch_notification')]
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

        return $this->render('admin/notification/index.html.twig', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);

    }
}
