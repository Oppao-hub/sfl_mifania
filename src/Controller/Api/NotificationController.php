<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'api_notifications_get', methods: ['GET'])]
    public function getNotifications(NotificationRepository $repo, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        // Fetch unread notifications using our robust repo method
        $notifications = $repo->findRecent($user, 20);

        $data = array_map(function($n) {
            /** @var \App\Entity\Notification $n */
            return [
                'id' => $n->getId(),
                'title' => $n->getTitle(),
                'message' => $n->getMessage(),
                'type' => $n->getType(),
                'isRead' => $n->isRead(),
                'targetUrl' => $n->getTargetUrl(),
                'createdAt' => $n->getCreatedAt()->format(\DateTimeInterface::ATOM)
            ];
        }, $notifications);

        return $this->json(['notifications' => $data]);
    }

    #[Route('/mark-read', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markAllRead(EntityManagerInterface $em, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        // DQL Update for efficiency
        $em->createQuery('
            UPDATE App\Entity\Notification n
            SET n.isRead = true
            WHERE n.recipient = :user AND n.isRead = false
        ')
        ->setParameter('user', $user)
        ->execute();

        return $this->json(['success' => true, 'message' => 'All notifications marked as read']);
    }
}
