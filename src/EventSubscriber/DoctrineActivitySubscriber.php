<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Service\ActivityLogger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManagerInterface;

#[AsDoctrineListener(event: Events::onFlush)]
class DoctrineActivitySubscriber
{
    public function __construct(private ActivityLogger $logger)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        if (!$em instanceof EntityManagerInterface) {
            return;
        }

        $uow = $em->getUnitOfWork();

        // 1. Handle Creations
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof ActivityLog) continue; // Don't log the log!

            // PASS FALSE to prevent infinite loop
            $this->logger->log('CREATE', $this->getDetails($entity, 'Created'), null, false);
        }

        // 2. Handle Updates
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof ActivityLog) continue;

            $changes = $uow->getEntityChangeSet($entity);
            $details = $this->getDetails($entity, 'Updated');

            // Add changed fields to description
            $keys = array_keys($changes);
            $details .= " (Fields: " . implode(', ', $keys) . ")";

            // PASS FALSE
            $this->logger->log('UPDATE', $details, null, false);
        }

        // 3. Handle Deletions
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof ActivityLog) continue;

            // PASS FALSE
            $this->logger->log('DELETE', $this->getDetails($entity, 'Deleted'), null, false);
        }
    }

    private function getDetails(object $entity, string $action): string
    {
        $class = (new \ReflectionClass($entity))->getShortName();

        $id = 'NEW';
        if (method_exists($entity, 'getId') && $entity->getId()) {
            $id = $entity->getId();
        }

        return "$action $class (ID: $id)";
    }
}
