<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SocketIoPublisher
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $publishUrl = 'http://127.0.0.1:3001/publish',
    ) {}

    public function publish(int $userId, string $event, array $data): void
    {
        try {
            $this->httpClient->request('POST', $this->publishUrl, [
                'json' => [
                    'userId' => $userId,
                    'event' => $event,
                    'data' => $data,
                ],
                'timeout' => 2,
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('Socket publish failed: {message}', [
                'message' => $e->getMessage(),
                'userId' => $userId,
                'event' => $event,
            ]);
        }
    }

    /**
     * @param int[] $userIds
     */
    public function publishToMany(array $userIds, string $event, array $data): void
    {
        foreach (array_unique($userIds) as $userId) {
            $this->publish((int) $userId, $event, $data);
        }
    }
}
