<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * @return ActivityLog[]
     */
    public function searchLogs(?string $query, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($query) {
            $qb->andWhere('a.action LIKE :query OR a.targetData LIKE :query OR u.email LIKE :query')
               ->setParameter('query', "'%' . { $query } . '%'");
        }

        return $qb->getQuery()->getResult();
    }
}
