<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\UserRepository;

/**
 * Pushes socket events so web dashboard and mobile clients refresh without manual reload.
 */
class RealtimeSync
{
    public function __construct(
        private SocketIoPublisher $socketIoPublisher,
        private UserRepository $userRepository,
    ) {}

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

    public function publishOrderChange(Order $order, string $action, ?string $statusLabel = null): void
    {
        $orderId = $order->getId();
        if ($orderId === null) {
            return;
        }

        $orderIdStr = (string) $orderId;
        $extra = [
            'orderId' => $orderIdStr,
            'status' => $statusLabel ?? $action,
            'message' => sprintf('Order #%s %s.', $orderIdStr, $action),
        ];

        $customerUser = $order->getCustomer()?->getUser();
        if ($customerUser) {
            $this->publishRefresh($customerUser->getId(), 'order', $action, $extra);
            $this->publishRefresh($customerUser->getId(), 'cart', 'updated');
        }

        foreach ($this->userRepository->findAllManagement() as $managementUser) {
            $this->publishRefresh($managementUser->getId(), 'order', $action, $extra);
        }
    }
}
