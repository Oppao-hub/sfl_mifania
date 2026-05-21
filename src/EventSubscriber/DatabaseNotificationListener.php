<?php

namespace App\EventSubscriber;

use App\Entity\Staff;
use App\Entity\Admin;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\Stock;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\NotificationPublisher;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

// Registering events for relevant entities
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

#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: Stock::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: Stock::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onDeleted', entity: Stock::class)]

#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: Order::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: Order::class)]
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
        $this->handleNotification('Removed', $entity, false, false);
    }

    private function handleNotification(string $action, object $entity, bool $includeLink = true, bool $flush = true): void
    {
        $currentUser = $this->security->getUser();
        // If it's a new order from a guest or API, we still want to notify management even if no $currentUser
        $isOrder = $entity instanceof Order;

        // 1. Identify Entity Type and target Route
        $name = 'Record';
        $route = 'app_dashboard';
        $titlePrefix = 'Registry';
        $type = 'system';

        if ($entity instanceof Staff) {
            $name = trim($entity->getFirstName() . ' ' . $entity->getLastName());
            $route = 'app_staff_show';
            $titlePrefix = 'Staff';
            $type = 'staff';
        } elseif ($entity instanceof Admin) {
            $name = trim($entity->getFirstName() . ' ' . $entity->getLastName());
            $route = 'app_admin_show';
            $titlePrefix = 'Admin';
            $type = 'admin';
        } elseif ($entity instanceof Customer) {
            $name = trim($entity->getFirstName() . ' ' . $entity->getLastName());
            $route = 'app_customer_show';
            $titlePrefix = 'Customer';
            $type = 'customer';
        } elseif ($entity instanceof Product) {
            $name = $entity->getName();
            $route = 'app_product_show';
            $titlePrefix = 'Product';
            $type = 'product';
        } elseif ($entity instanceof Stock) {
            $name = 'Stock for ' . ($entity->getProduct() ? $entity->getProduct()->getName() : 'Unknown Product');
            $route = 'app_stock_show';
            $titlePrefix = 'Stock';
            $type = 'stock';
        } elseif ($entity instanceof Order) {
            $name = 'Manifest #' . $entity->getId();
            $route = 'app_order_show';
            $titlePrefix = 'Order';
            $type = 'order';
        }

        // Final fallback
        if (empty($name) || $name === 'Record') {
            $name = $titlePrefix . ' #' . $entity->getId();
        }

        // 2. Logic to determine Recipients
        $recipients = [];
        $actorMessage = 'The system';
        $verb = 'processed';

        if ($currentUser instanceof User) {
            if (in_array('ROLE_ADMIN', $currentUser->getRoles())) {
                $actorMessage = 'Admin ' . $currentUser->getEmail();
            } elseif (in_array('ROLE_STAFF', $currentUser->getRoles())) {
                $actorMessage = 'Staff ' . $currentUser->getEmail();
            } else {
                $actorMessage = 'Customer ' . $currentUser->getEmail();
            }

            if ($isOrder) {
                $verb = ($action === 'Created') ? 'placed' : 'updated';
                // Orders notify BOTH Admins and Staff
                $recipients = $this->userRepository->findAllManagement();
            } elseif (in_array('ROLE_STAFF', $currentUser->getRoles())) {
                // If STAFF changes something -> notify ALL ADMINS
                $recipients = $this->userRepository->findAllAdmins();
            } elseif (in_array('ROLE_ADMIN', $currentUser->getRoles())) {
                // If ADMIN changes something -> notify OTHER ADMINS
                $recipients = $this->userRepository->findAllAdmins();
            }
        } else {
            // No current user (e.g., API order or background task)
            if ($isOrder) {
                $verb = 'placed';
                $recipients = $this->userRepository->findAllManagement();
            } else {
                // Fallback for other automated changes
                $recipients = $this->userRepository->findAllAdmins();
            }
        }

        // 3. Send Notifications
        foreach ($recipients as $recipient) {
            // Skip the person who actually performed the action
            if ($currentUser instanceof User && $recipient->getId() === $currentUser->getId()) {
                continue;
            }

            $message = "$actorMessage $verb: $name has been $action.";
            if ($isOrder && $action === 'Created') {
                $message = "$actorMessage has placed a new order: $name.";
            }

            $this->notificationPublisher->send(
                $recipient,
                "$titlePrefix $action",
                $message,
                $includeLink ? $route : 'app_dashboard',
                $includeLink ? ['id' => $entity->getId()] : [],
                $type,
                $flush
            );
        }
    }
}
