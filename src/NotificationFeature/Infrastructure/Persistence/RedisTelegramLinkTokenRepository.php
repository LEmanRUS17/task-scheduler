<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Persistence;

use App\NotificationFeature\Domain\Repository\TelegramLinkTokenRepositoryInterface;
use App\Shared\Cache\CacheStoreInterface;

final class RedisTelegramLinkTokenRepository implements TelegramLinkTokenRepositoryInterface
{
    private const string KEY_PREFIX = 'telegram_link_token_';

    public function __construct(
        private readonly CacheStoreInterface $cache,
    ) {
    }

    public function save(string $token, string $userId, int $ttlSeconds): void
    {
        $this->cache->set(self::KEY_PREFIX . $token, $userId, $ttlSeconds);
    }

    public function findUserIdByToken(string $token): ?string
    {
        $userId = $this->cache->get(self::KEY_PREFIX . $token);

        return \is_string($userId) ? $userId : null;
    }

    public function delete(string $token): void
    {
        $this->cache->delete(self::KEY_PREFIX . $token);
    }
}
