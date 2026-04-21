<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function searchByName(string $term): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.firstName LIKE :term OR c.lastName LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds a single Customer entity based on a search query (ID, Email, or Name).
     *
     * @param string $query The search term from the admin form.
     * @return Customer|null The found Customer entity or null if none is found.
     */
    public function findOneBySearch(string $query): ?Customer
    {
        $qb = $this->createQueryBuilder('c');
        $likeQuery = '%' . $query . '%';

        // Add a join to the User entity (assuming the association is named 'user')
        // We use an inner join because we expect customers to have an associated user for email lookup.
        $qb->innerJoin('c.user', 'u');


        // 1. Search by User Email (Exact Match on the joined entity)
        $qb->orWhere('u.email = :emailQuery')
            ->setParameter('emailQuery', $query);

        // 2. Search by ID (Exact Match, only if the query is a number)
        if (is_numeric($query)) {
            $qb->orWhere('c.id = :idQuery')
                ->setParameter('idQuery', $query);
        }

        // 3. Search by Name (Partial/Fuzzy Match on the Customer entity)
        $qb->orWhere('c.firstName LIKE :nameQuery')
            ->orWhere('c.lastName LIKE :nameQuery')
            ->setParameter('nameQuery', $likeQuery);

        // Execute the query, limit to 1 result, and return the single entity or null
        return $qb
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function searchByTerm(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id LIKE :query OR c.firstName LIKE :query OR c.lastName LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    public function getMonthlyRegistrations(int $monthsBack = 6): array
    {
        $data = [];
        for ($i = $monthsBack - 1; $i >= 0; $i--) {
            $start = new \DateTimeImmutable("first day of -$i month midnight");
            $end   = new \DateTimeImmutable("last day of -$i month 23:59:59");

            $count = $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getSingleScalarResult();

            $data[] = [
                'month' => $start->format('M'),
                'count' => (int) $count,
            ];
        }
        return $data;
    }

    public function getCityDistribution(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.city as label, COUNT(c.id) as value')
            ->where('c.city IS NOT NULL')
            ->groupBy('c.city')
            ->orderBy('value', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function getTopRewardHolders(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'w')
            ->innerJoin('c.wallet', 'w')
            ->orderBy('w.rewardPoints', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
