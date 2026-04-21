<?php
namespace App\Repository;

use App\Entity\Admin;
use App\Entity\User;
use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findRecent(?User $user, int $limit = 5): array
    {
        if (!$user || !$user->getId()) {
            return [];
        }

        return $this->createQueryBuilder('n')
            ->where('IDENTITY(n.recipient) = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('n.createdAt', 'DESC')
            ->addOrderBy('n.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnread(User $user): int
    {
        if (!$user || !$user->getId()) {
            return 0;
        }

        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('IDENTITY(n.recipient) = :userId')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('userId', $user->getId())
            ->setParameter('isRead', false)
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
