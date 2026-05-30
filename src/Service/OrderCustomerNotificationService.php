<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\UserRepository;
use BackedEnum;
use Psr\Log\LoggerInterface;

/** Persists customer inbox + push notifications for order/payment status changes. */
final class OrderCustomerNotificationService
{
    /** @var array<string, true> */
    private array $sentKeys = [];

    public function __construct(
        private NotificationPublisher $notificationPublisher,
        private OrderMailerService $orderMailerService,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changeSet
     */
    public function notifyFromChangeSet(Order $order, array $changeSet, bool $flush = true): bool
    {
        $orderId = $order->getId();
        if ($orderId === null) {
            return false;
        }

        $user = $this->resolveCustomerUser($order->getCustomer());
        if (!$user) {
            $this->logger->warning('Order customer notification skipped: no linked user.', [
                'orderId' => $orderId,
                'customerId' => $order->getCustomer()?->getId(),
            ]);

            return false;
        }

        $orderRef = $order->getDisplayReference();
        $sent = false;

        if (array_key_exists('orderStatus', $changeSet)) {
            $newStatus = $changeSet['orderStatus'][1] ?? $order->getOrderStatus();
            $statusLabel = $this->normalizeStatusLabel($newStatus, 'Processing');
            $this->notifyOrderStatusChange($order, $user, $orderRef, $statusLabel, sendEmail: true, flush: $flush);
            $sent = true;
        }

        if (array_key_exists('paymentStatus', $changeSet)) {
            $newStatus = $changeSet['paymentStatus'][1] ?? $order->getPaymentStatus();
            $statusLabel = $this->normalizeStatusLabel($newStatus, 'Pending');
            $this->notifyPaymentStatusChange($order, $user, $orderRef, $statusLabel, flush: $flush);
            $sent = true;
        }

        if (array_key_exists('paymentMethod', $changeSet)) {
            $newMethod = $changeSet['paymentMethod'][1] ?? $order->getPaymentMethod();
            $methodLabel = $this->normalizeStatusLabel($newMethod, 'Updated');
            $this->notifyPaymentMethodChange($order, $user, $orderRef, $methodLabel, flush: $flush);
            $sent = true;
        }

        return $sent;
    }

    public function notifyOrderStatusChange(
        Order $order,
        ?User $user = null,
        ?string $orderRef = null,
        ?string $statusLabel = null,
        bool $sendEmail = false,
        bool $flush = true,
    ): void {
        $orderId = $order->getId();
        if ($orderId === null) {
            return;
        }

        $recipient = $user ?? $this->resolveCustomerUser($order->getCustomer());
        if (!$recipient) {
            return;
        }

        $orderRef ??= $order->getDisplayReference();
        $statusLabel ??= $this->normalizeStatusLabel($order->getOrderStatus(), 'Processing');
        $dedupeKey = sprintf('order:%d:status:%s', $orderId, strtolower($statusLabel));

        if ($this->wasAlreadySent($dedupeKey)) {
            return;
        }

        $body = "Your order {$orderRef} is now {$statusLabel}.";

        $this->notificationPublisher->send(
            $recipient,
            'Order Status Updated',
            $body,
            'app_account_order_view',
            ['id' => $orderId],
            'order',
            $flush,
            $orderRef,
            $orderId,
        );

        $this->markSent($dedupeKey);

        $this->logger->info('Order status notification created.', [
            'orderId' => $orderId,
            'recipientId' => $recipient->getId(),
            'status' => $statusLabel,
        ]);

        if (!$sendEmail) {
            return;
        }

        try {
            $this->orderMailerService->sendStatusUpdateEmail($order);
        } catch (\Throwable $exception) {
            $this->logger->warning('Order status email failed.', [
                'orderId' => $orderId,
                'exception' => $exception,
            ]);
        }
    }

    public function notifyPaymentStatusChange(
        Order $order,
        ?User $user = null,
        ?string $orderRef = null,
        ?string $statusLabel = null,
        bool $flush = true,
    ): void {
        $orderId = $order->getId();
        if ($orderId === null) {
            return;
        }

        $recipient = $user ?? $this->resolveCustomerUser($order->getCustomer());
        if (!$recipient) {
            return;
        }

        $orderRef ??= $order->getDisplayReference();
        $statusLabel ??= $this->normalizeStatusLabel($order->getPaymentStatus(), 'Pending');
        $dedupeKey = sprintf('order:%d:payment:%s', $orderId, strtolower($statusLabel));

        if ($this->wasAlreadySent($dedupeKey)) {
            return;
        }

        $body = "The payment for order {$orderRef} is now {$statusLabel}.";

        $this->notificationPublisher->send(
            $recipient,
            'Payment Status Updated',
            $body,
            'app_account_order_view',
            ['id' => $orderId],
            'order',
            $flush,
            $orderRef,
            $orderId,
        );

        $this->markSent($dedupeKey);

        $this->logger->info('Order payment notification created.', [
            'orderId' => $orderId,
            'recipientId' => $recipient->getId(),
            'status' => $statusLabel,
        ]);
    }

    public function notifyPaymentMethodChange(
        Order $order,
        ?User $user = null,
        ?string $orderRef = null,
        ?string $methodLabel = null,
        bool $flush = true,
    ): void {
        $orderId = $order->getId();
        if ($orderId === null) {
            return;
        }

        $recipient = $user ?? $this->resolveCustomerUser($order->getCustomer());
        if (!$recipient) {
            return;
        }

        $orderRef ??= $order->getDisplayReference();
        $methodLabel ??= $this->normalizeStatusLabel($order->getPaymentMethod(), 'Updated');
        $dedupeKey = sprintf('order:%d:method:%s', $orderId, strtolower($methodLabel));

        if ($this->wasAlreadySent($dedupeKey)) {
            return;
        }

        $body = "The payment method for order {$orderRef} is now {$methodLabel}.";

        $this->notificationPublisher->send(
            $recipient,
            'Payment Method Updated',
            $body,
            'app_account_order_view',
            ['id' => $orderId],
            'order',
            $flush,
            $orderRef,
            $orderId,
        );

        $this->markSent($dedupeKey);

        $this->logger->info('Order payment method notification created.', [
            'orderId' => $orderId,
            'recipientId' => $recipient->getId(),
            'method' => $methodLabel,
        ]);
    }

    private function resolveCustomerUser(?Customer $customer): ?User
    {
        if (!$customer) {
            return null;
        }

        $user = $customer->getUser();
        if ($user instanceof User) {
            return $user;
        }

        return $this->userRepository->findOneByCustomer($customer);
    }

    private function normalizeStatusLabel(mixed $status, string $fallback): string
    {
        if ($status instanceof BackedEnum) {
            return (string) $status->value;
        }

        if (is_string($status) && trim($status) !== '') {
            return $status;
        }

        return $fallback;
    }

    private function wasAlreadySent(string $key): bool
    {
        return isset($this->sentKeys[$key]);
    }

    private function markSent(string $key): void
    {
        $this->sentKeys[$key] = true;
    }
}
