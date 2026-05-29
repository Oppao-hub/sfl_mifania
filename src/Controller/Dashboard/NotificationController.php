<?php

namespace App\Controller\Dashboard;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[IsGranted('ROLE_STAFF')]
#[Route('/dashboard/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/recent.json', name: 'app_notifications_recent', methods: ['GET'])]
    public function recent(NotificationRepository $notificationRepository, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $notifications = $notificationRepository->findForUser($user, 50);
        $payload = [];

        foreach ($notifications as $notification) {
            $payload[] = [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'type' => $notification->getType(),
                'isRead' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()?->format('Y-m-d H:i:s'),
                'relativeTime' => $notification->getCreatedAt()?->format('M d, g:i a'),
                'targetUrl' => $notification->getTargetUrl()
                    ? $this->generateUrl('app_notification_read_and_redirect', ['id' => $notification->getId()])
                    : '#',
            ];
        }

        $unreadCount = 0;
        foreach ($payload as $item) {
            if (!$item['isRead']) {
                ++$unreadCount;
            }
        }

        return new JsonResponse([
            'notifications' => $payload,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('', name: 'app_notification_index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository, #[CurrentUser] ?User $user): Response
    {
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('dashboard/notification/index.html.twig', [
            'notifications' => $notificationRepository->findForUser($user, 100),
        ]);
    }

    /**
     * Handles the "Mark Read" button click in the dropdown via AJAX
     */
    #[Route('/mark-read', name: 'app_notifications_mark_read', methods: ['POST'])]
    public function markAllAsRead(Request $request, EntityManagerInterface $em, #[CurrentUser] ?User $user): JsonResponse
    {
        // 1. Verify the CSRF token for security
        $token = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('mark_notifications_read', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        // 2. Get the currently logged-in user
        if (!$user || !$user->getId()) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // 3. Fast Bulk Update
        $em->createQuery('
            UPDATE App\Entity\Notification n
            SET n.isRead = true
            WHERE n.recipient = :user AND n.isRead = false
        ')
        ->setParameter('user', $user)
        ->execute();

        return new JsonResponse(['status' => 'success', 'message' => 'Notifications cleared']);
    }

    /**
     * Intercepts a notification click, marks it as read, and redirects to the target
     */
    #[Route('/{id}/read', name: 'app_notification_read_and_redirect', methods: ['GET'])]
    public function readAndRedirect(Notification $notification, EntityManagerInterface $em, #[CurrentUser] ?User $user): Response
    {
        // Security check: ensure the notification actually belongs to the logged in user
        if (!$user || $notification->getRecipient()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        // 1. If it's unread, mark it as read and save
        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $em->flush();
        }

        // 2. Get the target URL
        $targetUrl = $notification->getTargetUrl();

        // 3. Redirect the user
        if ($targetUrl) {
            return $this->redirect($targetUrl);
        }

        // Fallback if no URL exists
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/{id}/dismiss', name: 'app_notification_dismiss', methods: ['POST'])]
    public function dismiss(Request $request, Notification $notification, EntityManagerInterface $em, #[CurrentUser] ?User $user): JsonResponse
    {
        // Verify CSRF token
        $token = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('dismiss_notification', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        // Clean, universal security check!
        if (!$user || $notification->getRecipient()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $em->remove($notification);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
