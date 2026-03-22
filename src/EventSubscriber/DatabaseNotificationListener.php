<?php

namespace App\EventSubscriber;

use App\Entity\Staff;
use App\Entity\Admin;
use App\Entity\Customer;
use App\Repository\UserRepository;
use App\Service\NotificationPublisher;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

// Registering events for all three entity types
#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: Staff::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: Staff::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onDeleted', entity: Staff::class)]

#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: Admin::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: Admin::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onDeleted', entity: Admin::class)]

#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: Customer::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: Customer::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onDeleted', entity: Customer::class)]
class DatabaseNotificationListener
{
    public function __construct(
        private NotificationPublisher $notificationPublisher,
        private Security $security,
        private UserRepository $userRepository
    ) {}

    public function onCreated(object $entity): void
    {
        $this->handleNotification('Created', $entity);
    }

    public function onUpdated(object $entity): void
    {
        $this->handleNotification('Updated', $entity);
    }

    public function onDeleted(object $entity): void
    {
        // No link for deleted items as the route would 404
        $this->handleNotification('Removed', $entity, false);
    }

    private function handleNotification(string $action, object $entity, bool $includeLink = true): void
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser) return;

        // 1. Identify Entity Type and target Route
        $name = 'Record';
        $route = 'app_dashboard';
        $titlePrefix = 'Registry';

        if ($entity instanceof Staff) {
            $name = $entity->getFirstName() . ' ' . $entity->getLastName();
            $route = 'app_staff_show';
            $titlePrefix = 'Staff';
        } elseif ($entity instanceof Admin) {
            $name = $entity->getFirstName() . ' ' . $entity->getLastName();
            $route = 'app_admin_show';
            $titlePrefix = 'Admin';
        } elseif ($entity instanceof Customer) {
            $name = $entity->getFirstName() . ' ' . $entity->getLastName();
            $route = 'app_customer_show';
            $titlePrefix = 'Customer';
        }

        // 1. Fetch all Admin users
        $admins = $this->userRepository->findAllAdmins();

        // 2. Determine who made the change for the message context
        $actorRole = in_array('ROLE_ADMIN', $currentUser->getRoles()) ? 'An Admin' : 'A Staff Member';

        // 3. Loop through and notify every Admin
        foreach ($admins as $adminUser) {
            // Optional: Don't notify the admin if THEY are the one who made the change
            if ($adminUser === $currentUser) continue;

            $this->notificationPublisher->send(
                $adminUser,
                "$titlePrefix $action",
                "$actorRole updated the system: $name has been $action.",
                $includeLink ? $route : 'app_dashboard',
                $includeLink ? ['id' => $entity->getId()] : []
            );
        }
    }
}
