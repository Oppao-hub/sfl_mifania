<?php
namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Return recent notifications for a given user (most recent first).
     *
     * @param User|null $user
     * @param int $limit
     * @return Notification[]
     */
    public function findRecent(?User $user, int $limit = 5): array
    {
        if (!$user) {
            return [];
        }

        return $this->createQueryBuilder('n')
            ->andWhere('n.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count unread notifications for a user.
     *
     * @param User|null $user
     * @return int
     */
    public function countUnread(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Return notifications for a user (optionally paginated).
     *
     * @param User|null $user
     * @param int $limit
     * @param int $offset
     * @return Notification[]
     */
    public function findForUser(?User $user, int $limit = 50, int $offset = 0): array
    {
        if (!$user) {
            return [];
        }

        return $this->createQueryBuilder('n')
            ->andWhere('n.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // Add save method if you want simple persistence helper
    public function save(Notification $notification, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($notification);
        if ($flush) {
            $em->flush();
        }
    }
}
