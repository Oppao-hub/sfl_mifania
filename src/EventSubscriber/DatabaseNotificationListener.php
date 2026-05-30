<?php

namespace App\EventSubscriber;

use App\Entity\Staff;
use App\Entity\Admin;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\Stock;
use App\Entity\Order;
use App\Entity\Category;
use App\Entity\SubCategory;
use App\Entity\Story;
use App\Entity\QRTag;
use App\Entity\Reward;
use App\Entity\Redemption;
use App\Entity\Wallet;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\NotificationPublisher;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
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
#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: Category::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: Category::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onDeleted', entity: Category::class)]

#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: SubCategory::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: SubCategory::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onDeleted', entity: SubCategory::class)]

#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: Story::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: Story::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onDeleted', entity: Story::class)]

#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: QRTag::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: QRTag::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onDeleted', entity: QRTag::class)]

#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: Reward::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: Reward::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onDeleted', entity: Reward::class)]

#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: Redemption::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: Redemption::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onDeleted', entity: Redemption::class)]

#[AsEntityListener(event: Events::postPersist, method: 'onCreated', entity: Wallet::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUpdated', entity: Wallet::class)]
class DatabaseNotificationListener
{
    public function __construct(
        private NotificationPublisher $notificationPublisher,
        private Security $security,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
    ) {}

    public function onCreated(object $entity): void
    {
        $this->handleNotification('Created', $entity);
    }

    public function onUpdated(object $entity): void
    {
        // Customer order notifications are handled in OrderPostFlushSubscriber.
        if ($entity instanceof Order) {
            return;
        }

        $this->handleNotification('Updated', $entity);
    }

    public function onDeleted(object $entity): void
    {
        $this->handleNotification('Removed', $entity, false, false);
    }

    private function handleNotification(string $action, object $entity, bool $includeLink = true, bool $flush = true): void
    {
        $isOrder = $entity instanceof Order;
        if ($isOrder) {
            // Never nested-flush during order persistence (causes API order POST 500).
            $flush = false;
        }

        try {
            $this->dispatchNotification($action, $entity, $includeLink, $flush);
        } catch (\Throwable $exception) {
            $this->logger->error('Notification dispatch failed.', [
                'action' => $action,
                'entity' => $entity::class,
                'entityId' => method_exists($entity, 'getId') ? $entity->getId() : null,
                'exception' => $exception,
            ]);
        }
    }

    private function dispatchNotification(
        string $action,
        object $entity,
        bool $includeLink,
        bool $flush,
    ): void {
        $currentUser = $this->security->getUser();
        $isOrder = $entity instanceof Order;
        // If it's a new order from a guest or API, we still want to notify management even if no $currentUser

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
            $name = $entity->getDisplayReference();
            $route = 'app_order_show';
            $titlePrefix = 'Order';
            $type = 'order';
        } elseif ($entity instanceof Category) {
            $name = $entity->getName();
            $route = 'app_category_show';
            $titlePrefix = 'Category';
            $type = 'category';
        } elseif ($entity instanceof SubCategory) {
            $name = $entity->getName();
            $route = 'app_sub_category_show';
            $titlePrefix = 'Sub-category';
            $type = 'sub_category';
        } elseif ($entity instanceof Story) {
            $name = $entity->getTitle();
            $route = 'app_story_show';
            $titlePrefix = 'Story';
            $type = 'story';
        } elseif ($entity instanceof QRTag) {
            $name = 'QR Tag #' . $entity->getId();
            $route = 'app_qrTag_show';
            $titlePrefix = 'QR Tag';
            $type = 'qr';
        } elseif ($entity instanceof Reward) {
            $name = $entity->getName();
            $route = 'app_reward_show';
            $titlePrefix = 'Reward';
            $type = 'reward';
        } elseif ($entity instanceof Redemption) {
            $name = 'Redemption #' . $entity->getId();
            $route = 'app_reward_index';
            $titlePrefix = 'Redemption';
            $type = 'redemption';
        } elseif ($entity instanceof Wallet) {
            $name = 'Wallet #' . $entity->getId();
            $route = 'app_wallet_show';
            $titlePrefix = 'Wallet';
            $type = 'wallet';
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

            $routeParams = [];
            if ($includeLink && method_exists($entity, 'getId')) {
                $entityId = $entity->getId();
                if ($entityId !== null) {
                    $routeParams = ['id' => $entityId];
                }
            }

            $orderReference = $entity instanceof Order ? $entity->getDisplayReference() : null;

            $this->notificationPublisher->send(
                $recipient,
                "$titlePrefix $action",
                $message,
                $includeLink && $routeParams !== [] ? $route : 'app_dashboard',
                $routeParams,
                $type,
                $flush,
                $orderReference,
            );
        }
    }
}
