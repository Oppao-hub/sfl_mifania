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

    /**
     * Retrieves the top-selling products based on actual completed orders.
     */
    public function findTopSellers(int $limit = 3): array
    {
        return $this->createQueryBuilder('p')
            // We select the product, and create a hidden sum of all quantities sold
            ->select('p, SUM(oi.quantity) AS HIDDEN totalSales')
            // Join the OrderItems (Assuming your Product entity has a OneToMany relationship mapped to 'orderItems')
            // If the property is named differently in your Product entity, change 'oi' to match it.
            ->leftJoin('p.orderItems', 'oi')
            ->groupBy('p.id')
            ->orderBy('totalSales', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Fetch products based on their Master Category (e.g., 'Womenswear', 'Menswear')
     * * @return Product[] Returns an array of Product objects
     */
    public function findByMasterCategory(string $categoryName, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            // 1. Hop from Product to SubCategory
            ->join('p.subCategory', 'sc')
            // 2. Hop from SubCategory to Master Category
            ->join('sc.category', 'c')
            // 3. Check the Master Category's name
            ->andWhere('c.name = :categoryName')
            ->setParameter('categoryName', $categoryName)
            // Optional: Order by newest products first for the homepage!
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count products based on their Master Category
     */
    public function countByMasterCategory(string $categoryName): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->join('p.subCategory', 'sc')
            ->join('sc.category', 'c')
            ->andWhere('c.name = :categoryName')
            ->setParameter('categoryName', $categoryName)
            ->getQuery()
            ->getSingleScalarResult();
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

    public function countByCategoryName(string $categoryName): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            // Join the Product to its SubCategory
            ->join('p.subCategory', 'sc')
            // Join the SubCategory to its Parent Category
            ->join('sc.category', 'c')
            // Filter by the Parent Category's name
            ->andWhere('c.name = :categoryName')
            ->setParameter('categoryName', $categoryName)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
