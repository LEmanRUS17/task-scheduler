<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Repository;

/**
 * Short-lived token created by CreateTelegramLinkTokenInteractor and redeemed by
 * UnlinkedStateHandler when the user opens the bot via https://t.me/<bot>?start=<token>.
 * Backed by Redis (see RedisTelegramLinkTokenRepository) — not a relational table,
 * the token is meaningless once it expires or is redeemed.
 */
interface TelegramLinkTokenRepositoryInterface
{
    public function save(string $token, string $userId, int $ttlSeconds): void;

    public function findUserIdByToken(string $token): ?string;

    public function delete(string $token): void;
}
