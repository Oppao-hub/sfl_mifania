<?php

namespace App\Repository;

use App\Entity\WalletTopUpIntent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WalletTopUpIntent>
 */
class WalletTopUpIntentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletTopUpIntent::class);
    }

    public function findPendingByPrepareToken(string $prepareToken): ?WalletTopUpIntent
    {
        return $this->findOneBy([
            'prepareToken' => $prepareToken,
            'status' => WalletTopUpIntent::STATUS_PENDING,
        ]);
    }

    public function findByPaypalOrderId(string $paypalOrderId): ?WalletTopUpIntent
    {
        return $this->findOneBy(['paypalOrderId' => $paypalOrderId]);
    }
}
