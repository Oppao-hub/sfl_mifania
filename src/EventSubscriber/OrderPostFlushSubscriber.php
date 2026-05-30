<?php

namespace App\EventSubscriber;

use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use App\Service\OrderChangeBuffer;
use App\Service\OrderCustomerNotificationService;
use App\Service\RealtimeSync;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

/**
 * Creates customer notifications and socket events AFTER the order flush completes.
 * Running inside postUpdate + nested flush was preventing notifications from being saved.
 */
final class OrderPostFlushSubscriber
{
    /** @var list<string> */
    private const RELEVANT_FIELDS = ['orderStatus', 'paymentStatus', 'paymentMethod', 'totalAmount'];

    public function __construct(
        private OrderChangeBuffer $changeBuffer,
        private OrderCustomerNotificationService $orderCustomerNotificationService,
        private RealtimeSync $realtimeSync,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {}

    #[AsDoctrineListener(event: Events::postFlush)]
    public function onPostFlush(): void
    {
        if (!$this->changeBuffer->hasPending()) {
            return;
        }

        $pendingChanges = $this->changeBuffer->pullAll();
        $needsFlush = false;

        foreach ($pendingChanges as $orderId => $changeSet) {
            if (!$this->hasRelevantChanges($changeSet)) {
                continue;
            }

            $order = $this->entityManager->find(Order::class, (int) $orderId);
            if (!$order instanceof Order) {
                continue;
            }

            try {
                if ($this->orderCustomerNotificationService->notifyFromChangeSet($order, $changeSet, flush: false)) {
                    $needsFlush = true;
                }

                $statusLabel = $order->getOrderStatus()?->value ?? 'updated';
                $action = $statusLabel === OrderStatus::CANCELLED->value ? 'cancelled' : 'updated';
                $this->realtimeSync->publishOrderChange($order, $action, $statusLabel);
            } catch (\Throwable $exception) {
                $this->logger->error('Order post-flush notification/realtime failed.', [
                    'orderId' => $orderId,
                    'exception' => $exception,
                ]);
            }
        }

        if ($needsFlush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changeSet
     */
    private function hasRelevantChanges(array $changeSet): bool
    {
        foreach (self::RELEVANT_FIELDS as $field) {
            if (array_key_exists($field, $changeSet)) {
                return true;
            }
        }

        return false;
    }
}
