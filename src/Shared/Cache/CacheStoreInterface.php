<?php

declare(strict_types=1);

namespace App\Shared\Cache;

/**
 * Generic key-value store with TTL, for ephemeral data (tokens, codes, ...)
 * that does not belong in the primary relational database.
 */
interface CacheStoreInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $ttlSeconds): void;

    public function delete(string $key): void;
}
