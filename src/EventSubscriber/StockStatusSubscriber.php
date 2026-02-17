<?php

namespace App\EventSubscriber;

use App\Entity\Enum\StockStatus;
use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Stock::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Stock::class)]
class StockStatusSubscriber
{
    // If you need services (e.g., a logger), inject them here via the constructor
    // public function __construct(LoggerInterface $logger) { ... }

    public function prePersist(Stock $stock): void
    {
        $this->updateStatus($stock);
    }

    public function preUpdate(Stock $stock, PreUpdateEventArgs $event): void
    {
        // Important: When updating the entity *inside* preUpdate, you must notify Doctrine
        // if the status field is not part of the standard change set.
        $this->updateStatus($stock);

        // This is necessary to force Doctrine to track the change made inside the listener
        $em = $event->getObjectManager();
        $uow = $em->getUnitOfWork();
        $meta = $em->getClassMetadata(Stock::class);
        $uow->recomputeSingleEntityChangeSet($meta, $stock);
    }

    private function updateStatus(Stock $stock): void
    {
        if ($stock->getQuantity() == 0) {
            $stock->setStatus(StockStatus::OUT_OF_STOCK);
        } elseif ($stock->getQuantity() < 50) {
            $stock->setStatus(StockStatus::LOW_STOCK);
        } else {
            $stock->setStatus(StockStatus::IN_STOCK);
        }
    }
}
