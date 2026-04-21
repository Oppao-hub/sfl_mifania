<?php

namespace App\Controller\Frontend;

use App\Entity\ChatMessage;
use App\Entity\User;
use App\Repository\ChatMessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[IsGranted('ROLE_CUSTOMER')]
#[Route('/chat')]
final class CustomerChatController extends AbstractController
{
    private function getSupportUser(UserRepository $userRepository): ?User
    {
        // For now, just return the first Admin or Staff found
        return $userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :roleAdmin OR u.roles LIKE :roleStaff OR u.roles LIKE :roleSuperAdmin')
            ->setParameter('roleAdmin', '%"ROLE_ADMIN"%')
            ->setParameter('roleStaff', '%"ROLE_STAFF"%')
            ->setParameter('roleSuperAdmin', '%"ROLE_SUPER_ADMIN"%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    #[Route('/messages', name: 'app_customer_chat_messages', methods: ['GET'])]
    public function getMessages(
        ChatMessageRepository $chatMessageRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        #[CurrentUser] $currentUser
    ): JsonResponse {
        $messages = $chatMessageRepository->findSupportConversation($currentUser);

        // Direct DQL to ensure database is updated immediately
        $entityManager->createQuery('UPDATE App\Entity\ChatMessage m SET m.isRead = true WHERE m.recipient = :user AND m.isRead = false')
            ->setParameter('user', $currentUser)
            ->execute();

        $json = $serializer->serialize($messages, 'json', ['groups' => ['chat:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/mark-read', name: 'app_customer_chat_mark_read', methods: ['POST'])]
    public function markRead(
        EntityManagerInterface $entityManager,
        #[CurrentUser] $currentUser
    ): JsonResponse {
        $entityManager->createQuery('UPDATE App\Entity\ChatMessage m SET m.isRead = true WHERE m.recipient = :user AND m.isRead = false')
            ->setParameter('user', $currentUser)
            ->execute();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/unread-count', name: 'app_customer_chat_unread', methods: ['GET'])]
    public function getUnreadCount(
        ChatMessageRepository $chatMessageRepository,
        #[CurrentUser] $currentUser
    ): JsonResponse {
        $count = $chatMessageRepository->count([
            'recipient' => $currentUser,
            'isRead' => false
        ]);

        return new JsonResponse(['count' => $count]);
    }

    #[Route('/send', name: 'app_customer_chat_send', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        UserRepository $userRepository,
        ChatMessageRepository $chatMessageRepository,
        EntityManagerInterface $entityManager,
        HubInterface $hub,
        SerializerInterface $serializer,
        #[CurrentUser] $sender
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? null;

        if (!$content) {
            return new JsonResponse(['error' => 'Content is required'], Response::HTTP_BAD_REQUEST);
        }

        // 1. Try to find the last support person this customer talked to
        $lastMessage = $chatMessageRepository->createQueryBuilder('m')
            ->where('m.sender = :user OR m.recipient = :user')
            ->setParameter('user', $sender)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $recipient = null;
        if ($lastMessage) {
            // Pick the person who ISN'T the customer
            $recipient = $lastMessage->getSender()->getId() === $sender->getId() 
                ? $lastMessage->getRecipient() 
                : $lastMessage->getSender();
            
            // Double check they are still staff/admin
            $roles = $recipient->getRoles();
            if (!array_intersect(['ROLE_STAFF', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $roles)) {
                $recipient = null;
            }
        }

        // 2. Fallback to default support user if no previous contact found
        if (!$recipient) {
            $recipient = $this->getSupportUser($userRepository);
        }

        if (!$recipient) {
            return new JsonResponse(['error' => 'Support is currently unavailable'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $message = new ChatMessage();
        $message->setSender($sender);
        $message->setRecipient($recipient);
        $message->setContent($content);

        $entityManager->persist($message);
        $entityManager->flush();

        $jsonMessage = $serializer->serialize($message, 'json', ['groups' => ['chat:read']]);

        $update = new Update(
            [
                "/chat/user/{$recipient->getId()}",
                "/chat/user/{$sender->getId()}"
            ],
            $jsonMessage,
            false
        );
        $hub->publish($update);

        return new JsonResponse($jsonMessage, Response::HTTP_CREATED, [], true);
    }
}
