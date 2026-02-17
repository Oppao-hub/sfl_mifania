<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {
    }

    /**
     * @param bool $performFlush Set to FALSE if calling from inside a Doctrine Event Listener
     */
    public function log(string $action, string $targetData, ?User $targetUser = null, bool $performFlush = true): void
    {
        // 1. Determine User
        $user = $targetUser ?? $this->security->getUser();

        // 2. Prepare Log
        $log = new ActivityLog();

        if ($user instanceof User) {
            $log->setUser($user);
            $roles = implode(', ', $user->getRoles());
        } else {
            $roles = 'IS_AUTHENTICATED_ANONYMOUSLY';
        }

        $log->setRole($roles);
        $log->setAction($action);
        $log->setTargetData($targetData);
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($log);

        // 3. Handle Saving Strategy
        if ($performFlush) {
            // SCENARIO A: Called from Controller (Registration, etc.)
            // We save immediately.
            $this->em->flush();
        } else {
            // SCENARIO B: Called from onFlush Listener
            // We CANNOT flush here (infinite loop).
            // We must manually calculate the change set so the current flush picks it up.
            $uow = $this->em->getUnitOfWork();
            $uow->computeChangeSet($this->em->getClassMetadata(ActivityLog::class), $log);
        }
    }
}
