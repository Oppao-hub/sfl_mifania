<?php

namespace App\EventSubscriber;

use App\Entity\Admin;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\CustomerAddress;
use App\Entity\CustomerPaymentMethod;
use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ProductReview;
use App\Entity\QRTag;
use App\Entity\Redemption;
use App\Entity\Reward;
use App\Entity\Staff;
use App\Entity\Stock;
use App\Entity\Story;
use App\Entity\SubCategory;
use App\Entity\Wallet;
use App\Service\RealtimeAudienceResolver;
use App\Service\RealtimeSync;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

/**
 * Broadcasts entity changes from any source (mobile API, dashboard, checkout) to connected clients.
 */
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Admin::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Admin::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Admin::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Staff::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Staff::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Staff::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Customer::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Customer::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Customer::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: CustomerAddress::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: CustomerAddress::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: CustomerAddress::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: CustomerPaymentMethod::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: CustomerPaymentMethod::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: CustomerPaymentMethod::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Product::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Product::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Product::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Stock::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Stock::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Stock::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Order::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Order::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Order::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Category::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Category::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Category::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: SubCategory::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: SubCategory::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: SubCategory::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Story::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Story::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Story::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: QRTag::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: QRTag::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: QRTag::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Reward::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Reward::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Reward::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Redemption::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Redemption::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Redemption::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Wallet::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Wallet::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Wallet::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: Cart::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: Cart::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: Cart::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: CartItem::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: CartItem::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: CartItem::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: ProductReview::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onPostUpdate', entity: ProductReview::class)]
#[AsEntityListener(event: Events::preRemove, method: 'onPreRemove', entity: ProductReview::class)]
class RealtimeEntitySubscriber
{
    public function __construct(
        private RealtimeSync $realtimeSync,
        private RealtimeAudienceResolver $audienceResolver,
    ) {}

    public function onPostPersist(object $entity): void
    {
        $this->broadcastChange($entity, 'created');
    }

    public function onPostUpdate(object $entity, PostUpdateEventArgs $event): void
    {
        if ($entity instanceof Order && !$this->hasOrderRelevantChanges($event)) {
            return;
        }

        if ($entity instanceof Order) {
            $statusLabel = $entity->getOrderStatus()?->value ?? 'updated';
            $action = $statusLabel === OrderStatus::CANCELLED->value ? 'cancelled' : 'updated';
            $this->realtimeSync->publishOrderChange($entity, $action, $statusLabel);

            return;
        }

        $this->broadcastChange($entity, 'updated');
    }

    public function onPreRemove(object $entity, PreRemoveEventArgs $event): void
    {
        $entityType = $this->audienceResolver->resolveEntityType($entity);
        $entityId = $this->audienceResolver->resolveEntityId($entity);

        if ($entityType === null || $entityId === null) {
            return;
        }

        $customerUserId = $this->audienceResolver->resolveCustomerUserId($entity);

        if ($entity instanceof Order) {
            $this->realtimeSync->publishOrderRemoved($entityId, $customerUserId);

            return;
        }

        $this->realtimeSync->publishEntityRemoved($entityType, $entityId, $customerUserId);
    }

    private function broadcastChange(object $entity, string $action): void
    {
        $entityType = $this->audienceResolver->resolveEntityType($entity);
        if ($entityType === null) {
            return;
        }

        if ($entity instanceof Order) {
            $statusLabel = $entity->getOrderStatus()?->value ?? $action;
            $this->realtimeSync->publishOrderChange($entity, $action, $statusLabel);

            return;
        }

        $entityId = $this->audienceResolver->resolveEntityId($entity);
        $extra = [];
        if ($entityId !== null) {
            $extra['entityId'] = (string) $entityId;
        }

        $this->realtimeSync->publishEntityChange(
            $entityType,
            $action,
            $extra,
            $this->audienceResolver->resolveCustomerUserId($entity),
        );
    }

    private function hasOrderRelevantChanges(PostUpdateEventArgs $event): bool
    {
        $changeSet = $event->getEntityChangeSet();
        $relevantFields = ['orderStatus', 'paymentStatus', 'paymentMethod', 'totalAmount'];

        foreach ($relevantFields as $field) {
            if (array_key_exists($field, $changeSet)) {
                return true;
            }
        }

        return false;
    }
}
