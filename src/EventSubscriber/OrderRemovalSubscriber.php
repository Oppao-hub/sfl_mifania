<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Repository\UserRepository;
use App\Service\NotificationPublisher;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postRemove, entity: Order::class)]
#[AsDoctrineListener(event: Events::postFlush)]
class OrderRemovalSubscriber
{
    /** @var list<array{userId: int, orderId: int, orderRef: string}> */
    private array $pendingRemovals = [];

    public function __construct(
        private NotificationPublisher $notificationPublisher,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
    ) {}

    public function postRemove(Order $order, PostRemoveEventArgs $event): void
    {
        $user = $order->getCustomer()?->getUser();
        $orderId = $order->getId();
        if (!$user || $orderId === null) {
            return;
        }

        $this->pendingRemovals[] = [
            'userId' => (int) $user->getId(),
            'orderId' => $orderId,
            'orderRef' => $order->getDisplayReference(),
        ];
    }

    public function postFlush(): void
    {
        if ($this->pendingRemovals === []) {
            return;
        }

        $pending = $this->pendingRemovals;
        $this->pendingRemovals = [];

        foreach ($pending as $removal) {
            try {
                $user = $this->userRepository->find($removal['userId']);
                if (!$user) {
                    continue;
                }

                $this->notificationPublisher->send(
                    $user,
                    'Order Removed',
                    "Your order {$removal['orderRef']} was removed.",
                    'app_account_orders',
                    [],
                    'order',
                    true,
                    $removal['orderRef'],
                    $removal['orderId'],
                );
            } catch (\Throwable $exception) {
                $this->logger->error('Order removal notification failed.', [
                    'orderId' => $removal['orderId'],
                    'userId' => $removal['userId'],
                    'exception' => $exception,
                ]);
            }
        }
    }
}
