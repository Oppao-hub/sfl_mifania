<?php

namespace App\Repository;

use App\Entity\QRTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QRTag>
 */
class QRTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QRTag::class);
    }

    public function searchByName(string $term): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.firstName LIKE :term OR c.lastName LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->getQuery()
            ->getResult();
    }

    public function findAllImagePaths(): array
    {
        $result = $this->createQueryBuilder('q')
            ->select('q.qrImagePath')
            ->where('q.qrImagePath IS NOT NULL')
            ->groupBy('q.qrImagePath')
            ->getQuery()
            ->getScalarResult();

        // Convert [['qrImagePath' => 'path1'], ...] to ['path1', ...]
        return array_column($result, 'qrImagePath');
    }
    //    /**
//     * @return QRTag[] Returns an array of QRTag objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('q.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    //    public function findOneBySomeField($value): ?QRTag
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
