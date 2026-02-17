<?php
namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotificationPublisher
{
    private HubInterface $hub;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    /**
     * Publish a lightweight update for a user's notifications.
     */
    public function publishNotificationUpdate(int|string $userId): void
    {
        $topic = sprintf('/notifications/user/%s', $userId);

        $payload = json_encode([
            'topic' => $topic,
            'message' => 'notifications_updated',
            'timestamp' => (new \DateTime())->format(\DateTime::ATOM)
        ]);

        $update = new Update($topic, $payload);
        $this->hub->publish($update);
    }
}
