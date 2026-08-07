<?php

declare(strict_types=1);

namespace App\Shared\Cache;

use Psr\SimpleCache\CacheInterface;

final class RedisCacheStore implements CacheStoreInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    public function set(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->cache->set($key, $value, $ttlSeconds);
    }

    public function delete(string $key): void
    {
        $this->cache->delete($key);
    }
}
