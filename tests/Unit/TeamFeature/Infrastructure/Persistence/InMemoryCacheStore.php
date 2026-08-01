<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Infrastructure\Persistence;

use App\Shared\Cache\CacheStoreInterface;

final class InMemoryCacheStore implements CacheStoreInterface
{
    /** @var array<string, mixed> */
    private array $values = [];

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function set(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->values[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($this->values[$key]);
    }
}
