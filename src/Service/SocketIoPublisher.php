<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
class SocketIoPublisher
{
    public function __construct(private HttpClientInterface $httpClient) {}
    public function publish(int $userId, string $event, array $data): void
    {
        try {
            $this->httpClient->request('POST', 'http://localhost:3000/publish', [
                'json' => [
                    'userId' => $userId,
                    'event' => $event,
                    'data' => $data
                ]
            ]);
        } catch (\Exception $e) {
            // Log error
        }
    }
}
