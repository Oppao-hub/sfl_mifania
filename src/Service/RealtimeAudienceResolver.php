<?php

namespace App\Service;

use App\Entity\Admin;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\CustomerAddress;
use App\Entity\CustomerPaymentMethod;
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

final class RealtimeAudienceResolver
{
    /** @var array<class-string, string> */
    private const ENTITY_TYPES = [
        Admin::class => 'admin',
        Staff::class => 'staff',
        Customer::class => 'customer',
        CustomerAddress::class => 'address',
        CustomerPaymentMethod::class => 'payment_method',
        Product::class => 'product',
        Stock::class => 'stock',
        Order::class => 'order',
        Category::class => 'category',
        SubCategory::class => 'sub_category',
        Story::class => 'story',
        QRTag::class => 'qr',
        Reward::class => 'reward',
        Redemption::class => 'redemption',
        Wallet::class => 'wallet',
        Cart::class => 'cart',
        CartItem::class => 'cart',
        ProductReview::class => 'review',
    ];

    public function resolveEntityType(object $entity): ?string
    {
        return self::ENTITY_TYPES[$entity::class] ?? null;
    }

    public function resolveEntityId(object $entity): ?int
    {
        if (!method_exists($entity, 'getId')) {
            return null;
        }

        $id = $entity->getId();

        return is_int($id) ? $id : null;
    }

    public function resolveCustomerUserId(object $entity): ?int
    {
        if ($entity instanceof Customer) {
            return $entity->getUser()?->getId();
        }

        if ($entity instanceof CustomerAddress || $entity instanceof CustomerPaymentMethod) {
            return $entity->getCustomer()?->getUser()?->getId();
        }

        if ($entity instanceof Order) {
            return $entity->getCustomer()?->getUser()?->getId();
        }

        if ($entity instanceof Wallet || $entity instanceof Redemption) {
            return $entity->getCustomer()?->getUser()?->getId();
        }

        if ($entity instanceof Cart) {
            return $entity->getCustomer()?->getUser()?->getId();
        }

        if ($entity instanceof CartItem) {
            return $entity->getCart()?->getCustomer()?->getUser()?->getId();
        }

        if ($entity instanceof ProductReview) {
            return $entity->getCustomer()?->getUser()?->getId();
        }

        return null;
    }
}
