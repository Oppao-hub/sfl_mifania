<?php

namespace App\Service;

use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use App\Repository\UserRepository;

/**
 * Pushes socket events so web dashboard, storefront, and mobile clients refresh without manual reload.
 */
class RealtimeSync
{
    /** @var string[] */
    private const CATALOG_ENTITIES = [
        'product',
        'stock',
        'category',
        'sub_category',
        'story',
        'review',
    ];

    public function __construct(
        private SocketIoPublisher $socketIoPublisher,
        private UserRepository $userRepository,
    ) {}

    /**
     * @param array<string, mixed> $extra
     */
    public function publishEntityChange(
        string $entityType,
        string $action,
        array $extra = [],
        ?int $customerUserId = null,
    ): void {
        $payloadExtra = $this->normalizeExtra($entityType, $extra);

        foreach ($this->userRepository->findAllManagement() as $managementUser) {
            $this->publishRefresh($managementUser->getId(), $entityType, $action, $payloadExtra);
        }

        if ($customerUserId) {
            $this->publishRefresh($customerUserId, $entityType, $action, $payloadExtra);
        }

        if (\in_array($entityType, self::CATALOG_ENTITIES, true)) {
            $this->publishCatalogRefresh($entityType, $action, $payloadExtra);
        }
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function publishEntityRemoved(
        string $entityType,
        int $entityId,
        ?int $customerUserId = null,
        array $extra = [],
    ): void {
        $payloadExtra = $this->normalizeExtra($entityType, array_merge($extra, [
            'entityId' => (string) $entityId,
            'status' => 'deleted',
            'message' => sprintf('%s #%d was deleted.', $entityType, $entityId),
        ]));

        $this->publishEntityChange($entityType, 'deleted', $payloadExtra, $customerUserId);
    }

    public function publishOrderChange(Order $order, string $action, ?string $statusLabel = null): void
    {
        $orderId = $order->getId();
        if ($orderId === null) {
            return;
        }

        $orderIdStr = (string) $orderId;
        $extra = [
            'entityId' => $orderIdStr,
            'orderId' => $orderIdStr,
            'status' => $statusLabel ?? $action,
            'message' => sprintf('Order #%s %s.', $orderIdStr, $action),
        ];

        $customerUserId = $order->getCustomer()?->getUser()?->getId();
        $this->publishEntityChange('order', $action, $extra, $customerUserId);

        if ($customerUserId) {
            $this->publishRefresh($customerUserId, 'cart', 'updated', ['entity' => 'cart', 'action' => 'updated']);
        }
    }

    public function publishOrderRemoved(int $orderId, ?int $customerUserId = null): void
    {
        if ($orderId <= 0) {
            return;
        }

        $this->publishEntityRemoved('order', $orderId, $customerUserId, [
            'orderId' => (string) $orderId,
        ]);
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function publishCatalogRefresh(string $entity, string $action, array $extra = []): void
    {
        $payload = array_merge([
            'entity' => $entity,
            'action' => $action,
        ], $extra);

        $this->socketIoPublisher->publishToRoom('catalog', 'dashboard_refresh', $payload);
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function publishRefresh(int $userId, string $entity, string $action, array $extra = []): void
    {
        $payload = array_merge([
            'entity' => $entity,
            'action' => $action,
        ], $extra);

        $this->socketIoPublisher->publish($userId, 'dashboard_refresh', $payload);

        if ($entity === 'order' && isset($extra['orderId'])) {
            $this->socketIoPublisher->publish($userId, 'order_status_update', [
                'orderId' => (string) $extra['orderId'],
                'status' => $extra['status'] ?? $action,
                'message' => $extra['message'] ?? '',
                'type' => 'order',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function normalizeExtra(string $entityType, array $extra): array
    {
        if ($entityType === 'order' && isset($extra['entityId']) && !isset($extra['orderId'])) {
            $extra['orderId'] = (string) $extra['entityId'];
        }

        if ($entityType === 'order' && isset($extra['status'])) {
            $status = (string) $extra['status'];
            if ($status === OrderStatus::CANCELLED->value && !isset($extra['action'])) {
                $extra['action'] = 'cancelled';
            }
        }

        return $extra;
    }
}
