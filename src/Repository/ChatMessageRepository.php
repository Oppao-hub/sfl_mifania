<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    /**
     * @return ChatMessage[]
     */
    public function findConversation(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.recipient = :user2) OR (m.sender = :user2 AND m.recipient = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds all messages between a user and ANY staff/admin.
     * This allows a customer to see messages from multiple support agents in one thread.
     * 
     * @return ChatMessage[]
     */
    public function findSupportConversation(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->leftJoin('m.recipient', 'r')
            ->where('m.sender = :user AND (r.roles LIKE :staff OR r.roles LIKE :admin OR r.roles LIKE :super)')
            ->orWhere('m.recipient = :user AND (s.roles LIKE :staff OR s.roles LIKE :admin OR s.roles LIKE :super)')
            ->setParameter('user', $user)
            ->setParameter('staff', '%"ROLE_STAFF"%')
            ->setParameter('admin', '%"ROLE_ADMIN"%')
            ->setParameter('super', '%"ROLE_SUPER_ADMIN"%')
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
