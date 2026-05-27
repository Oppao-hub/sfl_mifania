<?php

namespace App\Controller\Frontend;

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

#[IsGranted('ROLE_USER')]
#[Route('/account/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'app_account_notifications', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository, #[CurrentUser] ?User $user): Response
    {
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Fetch the user's notifications (e.g., limit to 50 for the frontend)
        $notifications = $notificationRepository->findForUser($user, 50);

        return $this->render('frontend/notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/mark-read', name: 'app_account_notifications_mark_read', methods: ['POST'])]
    public function markAllAsRead(Request $request, EntityManagerInterface $em, #[CurrentUser] ?User $user): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('customer_mark_read', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        if (!$user || !$user->getId()) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $em->createQuery('
            UPDATE App\Entity\Notification n
            SET n.isRead = true
            WHERE n.recipient = :user AND n.isRead = false
        ')
        ->setParameter('user', $user)
        ->execute();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/{id}/read', name: 'app_account_notification_read_redirect', methods: ['GET'])]
    public function readAndRedirect(Notification $notification, EntityManagerInterface $em, #[CurrentUser] ?User $user): Response
    {
        if (!$user || $notification->getRecipient()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $em->flush();
        }

        if ($targetUrl = $notification->getTargetUrl()) {
            return $this->redirect($targetUrl);
        }

        return $this->redirectToRoute('app_account');
    }

    #[Route('/{id}/dismiss', name: 'app_account_notification_dismiss', methods: ['POST'])]
    public function dismiss(Request $request, Notification $notification, EntityManagerInterface $em, #[CurrentUser] ?User $user): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('customer_dismiss_notification', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        if (!$user || $notification->getRecipient()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $em->remove($notification);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
