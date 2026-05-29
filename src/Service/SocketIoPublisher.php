<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SocketIoPublisher
{
    /** @var string[] */
    private array $publishUrls;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $publishUrl = 'http://127.0.0.1:3001/publish',
        ?string $publishUrlFallback = null,
    ) {
        $this->publishUrls = array_values(array_unique(array_filter([
            trim($publishUrl),
            $publishUrlFallback ? trim($publishUrlFallback) : null,
        ])));
    }

    public function publish(int $userId, string $event, array $data): void
    {
        if ($this->publishUrls === []) {
            $this->logger->error('Socket publish skipped: no publish URLs configured.');

            return;
        }

        $payload = [
            'userId' => $userId,
            'event' => $event,
            'data' => $data,
        ];

        foreach ($this->publishUrls as $index => $publishUrl) {
            if ($this->attemptPublish($publishUrl, $payload, $userId, $event, $index === 0)) {
                return;
            }
        }

        $this->logger->error('Socket publish failed on all configured URLs.', [
            'publishUrls' => $this->publishUrls,
            'userId' => $userId,
            'event' => $event,
        ]);
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

    /**
     * Broadcast to a shared Socket.IO room (e.g. catalog updates for all clients).
     *
     * @param array<string, mixed> $data
     */
    public function publishToRoom(string $room, string $event, array $data): void
    {
        if ($this->publishUrls === []) {
            $this->logger->error('Socket room publish skipped: no publish URLs configured.');

            return;
        }

        $payload = [
            'room' => $room,
            'event' => $event,
            'data' => $data,
        ];

        foreach ($this->publishUrls as $index => $publishUrl) {
            if ($this->attemptPublish($publishUrl, $payload, 0, $event, $index === 0, $room)) {
                return;
            }
        }

        $this->logger->error('Socket room publish failed on all configured URLs.', [
            'publishUrls' => $this->publishUrls,
            'room' => $room,
            'event' => $event,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function attemptPublish(
        string $publishUrl,
        array $payload,
        int $userId,
        string $event,
        bool $isPrimary,
        ?string $room = null,
    ): bool {
        try {
            $response = $this->httpClient->request('POST', $publishUrl, [
                'json' => $payload,
                'timeout' => 5,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                $this->logger->warning('Socket publish HTTP error: {status}', [
                    'status' => $statusCode,
                    'publishUrl' => $publishUrl,
                    'userId' => $userId,
                    'event' => $event,
                    'primary' => $isPrimary,
                ]);

                return false;
            }

            $this->logger->info('Socket event published.', [
                'publishUrl' => $publishUrl,
                'userId' => $userId > 0 ? $userId : null,
                'room' => $room,
                'event' => $event,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->warning('Socket publish failed: {message}', [
                'message' => $e->getMessage(),
                'publishUrl' => $publishUrl,
                'userId' => $userId,
                'event' => $event,
                'primary' => $isPrimary,
            ]);

            return false;
        }
    }
}
