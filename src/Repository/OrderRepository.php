<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function searchByIdOrCustomerName(string $term): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.id LIKE :term OR o.orderDate LIKE :term OR o.paymentMethod LIKE :term OR o.paymentStatus LIKE :term OR o.orderStatus LIKE :term')
            ->setParameter('term', $term . '%')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithOrderItems(): array
    {
        return $this->createQueryBuilder('o')
            ->select('o', 'oi')
            ->leftJoin('o.orderItems', 'oi')
            ->getQuery()
            ->getResult();
    }
}
