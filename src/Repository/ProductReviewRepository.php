<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ProductReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductReview>
 */
class ProductReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductReview::class);
    }

    public function findOneForCustomerOrderProduct(
        Customer $customer,
        Order $order,
        Product $product,
    ): ?ProductReview {
        return $this->findOneBy([
            'customer' => $customer,
            'order' => $order,
            'product' => $product,
        ]);
    }
}
