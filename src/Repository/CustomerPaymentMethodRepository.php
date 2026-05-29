<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\CustomerPaymentMethod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerPaymentMethod>
 */
class CustomerPaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerPaymentMethod::class);
    }

    /**
     * @return CustomerPaymentMethod[]
     */
    public function findByCustomerOrdered(Customer $customer): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('pm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCustomerAndProvider(Customer $customer, string $providerType): ?CustomerPaymentMethod
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.customer = :customer')
            ->andWhere('pm.providerType = :providerType')
            ->setParameter('customer', $customer)
            ->setParameter('providerType', $providerType)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
