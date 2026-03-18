<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    use DatatableTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function searchByIdOrName(string $term): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id LIKE :term OR p.productName LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->getQuery()
            ->getResult();
    }

    public function countActiveProducts(): int
    {
        return $this->createQueryBuilder('p')
            // Select the count of the primary key (usually 'id')
            ->select('COUNT(p.id)')

            // Add your condition here (e.g., assuming a 'isActive' property)
            ->andWhere('p.isActive = :status')
            ->setParameter('status', true)

            // Execute the query and return the single scalar (integer) result
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->andWhere('c.id = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->getQuery()
            ->getResult();
    }

    public function findTopSellingProducts(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.name as name', 'SUM(oi.quantity) as unitsSold', 'SUM(oi.quantity * p.price) as revenue')
            ->leftJoin('p.orderItems', 'oi')
            ->groupBy('p.id')
            ->orderBy('unitsSold', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByGender(string $gender)
    {
        return $this->createQueryBuilder('p')
            ->where('p.gender = :gender')
            ->orWhere('p.gender = :unisex') // Include Unisex items
            ->setParameter('gender', $gender)
            ->setParameter('unisex', 'Unisex')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(20) // Limit results for homepage
            ->getQuery()
            ->getResult();
    }

    public function findByMaxPrice(int $price): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.price <= :price')
            ->setParameter('price', $price)
            ->orderBy('p.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchByTerm(string $query): array
    {
        return $this->createQueryBuilder('p')
            // This searches if the query is ANYWHERE inside the name or description
            ->andWhere('p.name LIKE :query OR p.description LIKE :query')
            // The '%' symbols act as wildcards (e.g., %Organic%)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.id', 'DESC') // Show newest products first
            ->setMaxResults(50)       // Limit to 50 so it doesn't slow down your app
            ->getQuery()
            ->getResult();
    }
}
