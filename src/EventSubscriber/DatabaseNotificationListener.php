<?php

namespace App\EventSubscriber;

use App\Entity\Staff;
use App\Entity\Admin;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
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

#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: Product::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: Product::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onDeleted', entity: Product::class)]
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
        if (!$currentUser instanceof User) return;

        // 1. Identify Entity Type and target Route
        $name = 'Record';
        $route = 'app_dashboard';
        $titlePrefix = 'Registry';
        $type = 'system';

        if ($entity instanceof Staff) {
            $name = $entity->getFirstName() . ' ' . $entity->getLastName();
            $route = 'app_staff_show';
            $titlePrefix = 'Staff';
            $type = 'staff';
        } elseif ($entity instanceof Admin) {
            $name = $entity->getFirstName() . ' ' . $entity->getLastName();
            $route = 'app_admin_show';
            $titlePrefix = 'Admin';
            $type = 'admin';
        } elseif ($entity instanceof Customer) {
            $name = $entity->getFirstName() . ' ' . $entity->getLastName();
            $route = 'app_customer_show';
            $titlePrefix = 'Customer';
            $type = 'customer';
        } elseif ($entity instanceof Product) {
            $name = $entity->getName();
            $route = 'app_product_show';
            $titlePrefix = 'Product';
            $type = 'product';
        }

        // 1. Fetch all management users (Admins and Staff)
        $managers = $this->userRepository->findAllManagement();

        // 2. Determine who made the change for the message context
        $actorRole = in_array('ROLE_ADMIN', $currentUser->getRoles()) ? 'An Admin' : 'A Staff Member';

        // 3. Loop through and notify every manager
        /** @var User $managerUser */
        foreach ($managers as $managerUser) {
            // Skip the person who actually performed the action
            if ($managerUser->getId() === $currentUser->getId()) {
                continue;
            }

            $this->notificationPublisher->send(
                $managerUser,
                "$titlePrefix $action",
                "$actorRole updated the system: $name has been $action.",
                $includeLink ? $route : 'app_dashboard',
                $includeLink ? ['id' => $entity->getId()] : [],
                $type
            );
        }
    }
}
