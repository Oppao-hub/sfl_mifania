<?php

namespace App\Controller\Dashboard;

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

#[IsGranted('ROLE_STAFF')]
#[Route('/dashboard/chat')]
final class ChatController extends AbstractController
{
    #[Route('/', name: 'app_chat', methods: ['GET'])]
    public function index(UserRepository $userRepository, ChatMessageRepository $chatMessageRepository, #[CurrentUser] $currentUser): Response
    {
        // 1. Fetch all staff and admins (Always visible to each other)
        $staffAndAdmins = $userRepository->createQueryBuilder('u')
            ->where('u.id != :currentUserId')
            ->andWhere('u.roles LIKE :roleStaff OR u.roles LIKE :roleAdmin OR u.roles LIKE :roleSuperAdmin')
            ->setParameter('currentUserId', $currentUser->getId())
            ->setParameter('roleStaff', '%"ROLE_STAFF"%')
            ->setParameter('roleAdmin', '%"ROLE_ADMIN"%')
            ->setParameter('roleSuperAdmin', '%"ROLE_SUPER_ADMIN"%')
            ->getQuery()
            ->getResult();

        // 2. Fetch all customers who have a history with ANY staff or admin
        $queryBuilder = $chatMessageRepository->createQueryBuilder('m');
        $messagedUserIds = $queryBuilder
            ->select('IDENTITY(m.sender) as sId, IDENTITY(m.recipient) as rId')
            ->leftJoin('m.sender', 's')
            ->leftJoin('m.recipient', 'r')
            ->where('(s.roles LIKE :roleCustomer AND (r.roles LIKE :roleStaff OR r.roles LIKE :roleAdmin OR r.roles LIKE :roleSuperAdmin))')
            ->orWhere('(r.roles LIKE :roleCustomer AND (s.roles LIKE :roleStaff OR s.roles LIKE :roleAdmin OR s.roles LIKE :roleSuperAdmin))')
            ->setParameter('roleCustomer', '%"ROLE_CUSTOMER"%')
            ->setParameter('roleStaff', '%"ROLE_STAFF"%')
            ->setParameter('roleAdmin', '%"ROLE_ADMIN"%')
            ->setParameter('roleSuperAdmin', '%"ROLE_SUPER_ADMIN"%')
            ->getQuery()
            ->getScalarResult();

        $ids = [];
        foreach ($messagedUserIds as $row) {
            $ids[] = $row['sId'];
            $ids[] = $row['rId'];
        }
        $ids = array_unique(array_filter($ids, fn($id) => $id != $currentUser->getId()));

        $customers = [];
        if (!empty($ids)) {
            $customers = $userRepository->createQueryBuilder('u')
                ->where('u.id IN (:ids)')
                ->andWhere('u.roles LIKE :roleCustomer')
                ->setParameter('ids', $ids)
                ->setParameter('roleCustomer', '%"ROLE_CUSTOMER"%')
                ->getQuery()
                ->getResult();
        }

        $contacts = array_merge($staffAndAdmins, $customers);

        // 3. Initial state for unread counts and last messages
        $unreadCounts = [];
        $lastMessages = [];
        foreach ($contacts as $contact) {
            $unreadCounts[$contact->getId()] = $chatMessageRepository->count([
                'sender' => $contact,
                'recipient' => $currentUser,
                'isRead' => false
            ]);

            // Find last message in conversation
            $lastMsg = $chatMessageRepository->createQueryBuilder('m')
                ->where('(m.sender = :u1 AND m.recipient = :u2) OR (m.sender = :u2 AND m.recipient = :u1)')
                ->setParameter('u1', $currentUser)
                ->setParameter('u2', $contact)
                ->orderBy('m.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            
            if ($lastMsg) {
                $lastMessages[$contact->getId()] = $lastMsg->getContent();
            }
        }

        // 4. Fetch active users
        $activeUsers = [];
        try {
            $activeUsers = $userRepository->createQueryBuilder('u')
                ->where('u.lastActiveAt >= :activeLimit')
                ->andWhere('u.roles LIKE :roleStaff OR u.roles LIKE :roleAdmin OR u.roles LIKE :roleSuperAdmin OR u.roles LIKE :roleCustomer')
                ->setParameter('activeLimit', new \DateTimeImmutable('-15 minutes'))
                ->setParameter('roleStaff', '%"ROLE_STAFF"%')
                ->setParameter('roleAdmin', '%"ROLE_ADMIN"%')
                ->setParameter('roleSuperAdmin', '%"ROLE_SUPER_ADMIN"%')
                ->setParameter('roleCustomer', '%"ROLE_CUSTOMER"%')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {}

        return $this->render('dashboard/chat/index.html.twig', [
            'contacts' => $contacts,
            'activeUsers' => $activeUsers,
            'initialUnread' => $unreadCounts,
            'initialLastMsgs' => $lastMessages
        ]);
    }

    #[Route('/messages/{id}', name: 'app_chat_messages', methods: ['GET'])]
    public function getMessages(
        User $recipient,
        ChatMessageRepository $chatMessageRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        #[CurrentUser] $currentUser
    ): JsonResponse {
        $messages = $chatMessageRepository->findConversation($currentUser, $recipient);

        // Direct DQL to ensure database is updated immediately
        $entityManager->createQuery('UPDATE App\Entity\ChatMessage m SET m.isRead = true WHERE m.sender = :recipient AND m.recipient = :user AND m.isRead = false')
            ->setParameter('recipient', $recipient)
            ->setParameter('user', $currentUser)
            ->execute();

        // Check if this contact is a customer and who is assigned
        $assignedTo = $recipient->getAssignedSupport();
        $assignedInfo = null;
        if ($assignedTo) {
            $profile = $assignedTo->getAdmin() ?? $assignedTo->getStaff();
            $assignedInfo = [
                'id' => $assignedTo->getId(),
                'name' => $profile ? $profile->getFirstName() . ' ' . $profile->getLastName() : $assignedTo->getEmail()
            ];
        }

        $jsonMessages = $serializer->serialize($messages, 'json', ['groups' => ['chat:read']]);
        
        return new JsonResponse([
            'messages' => json_decode($jsonMessages),
            'assignedSupport' => $assignedInfo,
            'isCustomer' => in_array('ROLE_CUSTOMER', $recipient->getRoles())
        ], Response::HTTP_OK);
    }

    #[Route('/claim/{id}', name: 'app_chat_claim', methods: ['POST'])]
    public function claimChat(User $customer, EntityManagerInterface $entityManager, #[CurrentUser] $currentUser): JsonResponse
    {
        if (!in_array('ROLE_CUSTOMER', $customer->getRoles())) {
            return new JsonResponse(['error' => 'Only customers can be claimed'], Response::HTTP_BAD_REQUEST);
        }

        $customer->setAssignedSupport($currentUser);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'name' => $currentUser->getEmail()]);
    }

    #[Route('/unclaim/{id}', name: 'app_chat_unclaim', methods: ['POST'])]
    public function unclaimChat(User $customer, EntityManagerInterface $entityManager): JsonResponse
    {
        $customer->setAssignedSupport(null);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/send', name: 'app_chat_send', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        HubInterface $hub,
        SerializerInterface $serializer,
        #[CurrentUser] $sender
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $recipientId = $data['recipientId'] ?? null;
        $content = $data['content'] ?? null;

        if (!$recipientId || !$content) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $recipient = $userRepository->find($recipientId);
        if (!$recipient) {
            return new JsonResponse(['error' => 'Recipient not found'], Response::HTTP_NOT_FOUND);
        }

        // Auto-claim if it's a customer and not assigned
        if (in_array('ROLE_CUSTOMER', $recipient->getRoles()) && !$recipient->getAssignedSupport()) {
            $recipient->setAssignedSupport($sender);
        }

        $message = new ChatMessage();
        $message->setSender($sender);
        $message->setRecipient($recipient);
        $message->setContent($content);

        $entityManager->persist($message);
        $entityManager->flush();

        // Publish to Mercure
        $jsonMessage = $serializer->serialize($message, 'json', ['groups' => ['chat:read']]);

        $update = new Update(
            ["/chat/user/{$recipient->getId()}", "/chat/user/{$sender->getId()}"],
            $jsonMessage,
            false
        );
        $hub->publish($update);

        return new JsonResponse($jsonMessage, Response::HTTP_CREATED, [], true);
    }
}
