<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\CustomerAddress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerAddress>
 */
class CustomerAddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerAddress::class);
    }

    /**
     * @return CustomerAddress[]
     */
    public function findByCustomerOrdered(Customer $customer): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.customer = :customer')
            ->setParameter('customer', $customer)
            ->addOrderBy('a.isDefault', 'DESC')
            ->addOrderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDefaultForCustomer(Customer $customer): ?CustomerAddress
    {
        return $this->findOneBy(['customer' => $customer, 'isDefault' => true]);
    }

    public function countForCustomer(Customer $customer): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.customer = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
