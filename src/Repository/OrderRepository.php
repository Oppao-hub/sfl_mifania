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

    public function searchByTerm(string $query): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.id LIKE :query OR o.orderStatus LIKE :query OR o.paymentStatus LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('o.id', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    public function getMonthlySalesData(int $monthsBack = 6): array
    {
        $salesData = [];

        // Loop backwards from 5 months ago up to this current month (6 months total)
        for ($i = $monthsBack - 1; $i >= 0; $i--) {
            $start = new \DateTime("first day of -$i month midnight");
            $end   = new \DateTime("last day of -$i month 23:59:59");

            $total = $this->createQueryBuilder('o')
                ->select('SUM(o.totalAmount)')
                ->where('o.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getSingleScalarResult();

            $salesData[] = [
                'month' => $start->format('M'), // Formats to 'Jan', 'Feb', etc.
                'total' => (float) ($total ?? 0), // Fallback to 0 if no sales
            ];
        }

        return $salesData;
    }
}
