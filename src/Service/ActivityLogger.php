<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogger
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
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

            // Polish: Grab the highest privilege role for a cleaner UI badge
            $roles = $user->getRoles();
            $primaryRole = in_array('ROLE_ADMIN', $roles) ? 'ROLE_ADMIN' : 'ROLE_USER';
            $log->setRole($primaryRole);
        } else {
            // Polish: Cleaner system fallback string
            $log->setRole('SYSTEM');
        }

        $log->setAction($action);
        $log->setTargetData($targetData);

        // (If your ActivityLog __construct already sets this, you can safely remove this line)
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($log);

        // 3. Handle Saving Strategy
        if ($performFlush) {
            // SCENARIO A: Called from Controller (Registration, etc.)
            $this->em->flush();
        } else {
            // SCENARIO B: Called from onFlush Listener
            // Compute the change set so Doctrine inserts this log in the current transaction queue
            $uow = $this->em->getUnitOfWork();
            $uow->computeChangeSet($this->em->getClassMetadata(ActivityLog::class), $log);
        }
    }
}
