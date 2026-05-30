<?php

namespace App\Service;

/**
 * Captures order field changes during preUpdate (ORM 3.x no longer exposes
 * change sets on PostUpdateEventArgs) for postFlush listeners.
 */
final class OrderChangeBuffer
{
    /** @var array<int, array<string, array{0: mixed, 1: mixed}>> */
    private array $changes = [];

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changeSet
     */
    public function record(int $orderId, array $changeSet): void
    {
        if ($changeSet === []) {
            return;
        }

        $this->changes[$orderId] = array_merge($this->changes[$orderId] ?? [], $changeSet);
    }

    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function get(int $orderId): array
    {
        return $this->changes[$orderId] ?? [];
    }

    public function hasPending(): bool
    {
        return $this->changes !== [];
    }

    /**
     * @return array<int, array<string, array{0: mixed, 1: mixed}>>
     */
    public function pullAll(): array
    {
        $pending = $this->changes;
        $this->changes = [];

        return $pending;
    }

    public function flush(): void
    {
        $this->changes = [];
    }
}
