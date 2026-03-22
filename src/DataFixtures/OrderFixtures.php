<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Customer;
use App\Entity\Enum\OrderStatus;
use App\Entity\Enum\PaymentMethod;
use App\Entity\Enum\PaymentStatus;
use App\Entity\OrderItem;
use App\Entity\Order;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Customer $customer */
        $customer = $this->getReference(UserFixtures::CUSTOMER_REFERENCE, Customer::class);

        /** @var Product $product1 */
        $product1 = $this->getReference(ProductFixtures::PRODUCT_REFERENCE . '-0', Product::class);
        /** @var Product $product2 */
        $product2 = $this->getReference(ProductFixtures::PRODUCT_REFERENCE . '-1', Product::class);

        // Create Order Items
        $orderItem1 = new OrderItem();
        $orderItem1->setProduct($product1);
        $orderItem1->setQuantity(1);
        $orderItem1->setPrice($product1->getPrice());
        $orderItem1->setSubtotal($product1->getPrice());

        $orderItem2 = new OrderItem();
        $orderItem2->setProduct($product2);
        $orderItem2->setQuantity(2);
        $orderItem2->setPrice($product2->getPrice());
        $orderItem2->setSubtotal($product2->getPrice() * 2);

        // Create Order
        $order = new Order();
        $order->setCustomer($customer);
        $order->setTotalAmount($orderItem1->getSubtotal() + $orderItem2->getSubtotal());
        $order->setPaymentMethod(PaymentMethod::CASH);
        $order->setPaymentStatus(PaymentStatus::PAID);
        $order->setOrderStatus(OrderStatus::PENDING);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setUpdatedAt(new \DateTimeImmutable());
        $order->addOrderItem($orderItem1);
        $order->addOrderItem($orderItem2);

        // Persist everything
        $manager->persist($order);
        $manager->persist($orderItem1);
        $manager->persist($orderItem2);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ProductFixtures::class,
        ];
    }
}
