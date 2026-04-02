<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Service\ActivityLogger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class DoctrineActivitySubscriber
{
    /**
     * Entities that should bypass the automatic activity log
     * to prevent infinite persistence loops or database noise.
     */
    private const IGNORED_ENTITIES = [
        ActivityLog::class,
        // App\Entity\Session::class, // Add future system entities here
    ];

    // Upgraded to PHP 8.1 readonly property
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        // 1. Handle Creations
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($this->isIgnored($entity)) {
                continue;
            }

            $this->logger->log(
                'CREATE',
                $this->formatLogMessage($entity, 'Created'),
                null,
                false
            );
        }

        // 2. Handle Updates
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($this->isIgnored($entity)) {
                continue;
            }

            $changes = $uow->getEntityChangeSet($entity);
            $changedFields = implode(', ', array_keys($changes));

            $message = sprintf(
                '%s (Modified fields: %s)',
                $this->formatLogMessage($entity, 'Updated'),
                $changedFields
            );

            $this->logger->log('UPDATE', $message, null, false);
        }

        // 3. Handle Deletions
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($this->isIgnored($entity)) {
                continue;
            }

            $this->logger->log(
                'DELETE',
                $this->formatLogMessage($entity, 'Deleted'),
                null,
                false
            );
        }
    }

    /**
     * Checks if the entity belongs to the IGNORED_ENTITIES list.
     */
    private function isIgnored(object $entity): bool
    {
        foreach (self::IGNORED_ENTITIES as $ignoredClass) {
            if ($entity instanceof $ignoredClass) {
                return true;
            }
        }

        return false;
    }

    /**
     * Formats the base description for the log entry.
     */
    private function formatLogMessage(object $entity, string $action): string
    {
        $className = (new \ReflectionClass($entity))->getShortName();

        $id = method_exists($entity, 'getId') && $entity->getId() !== null
            ? (string) $entity->getId()
            : 'NEW';

        return sprintf('%s %s (ID: %s)', $action, $className, $id);
    }
}
