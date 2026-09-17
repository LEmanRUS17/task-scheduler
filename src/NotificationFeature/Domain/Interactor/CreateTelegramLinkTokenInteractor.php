<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Interactor;

use App\NotificationFeature\Domain\Exception\TelegramChatAlreadyLinkedException;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use App\NotificationFeature\Domain\Repository\TelegramLinkTokenRepositoryInterface;

final class CreateTelegramLinkTokenInteractor
{
    /**
     * Matches the 1-hour TTL used by the reference telegram_token store (job vault:
     * Tarantool/spaces/telegram_token.md — "Автоматически удаляется через час").
     */
    private const int TOKEN_TTL_SECONDS = 3600;

    public function __construct(
        private readonly TelegramLinkTokenRepositoryInterface $tokens,
        private readonly TelegramChatRepositoryInterface $telegramChats,
    ) {
    }

    /**
     * @throws TelegramChatAlreadyLinkedException when the user already has a linked chat
     */
    public function create(string $userId): string
    {
        if ($this->telegramChats->isLinkedForUserId($userId)) {
            throw TelegramChatAlreadyLinkedException::forUserId($userId);
        }

        $token = bin2hex(random_bytes(32));

        $this->tokens->save($token, $userId, self::TOKEN_TTL_SECONDS);

        return $token;
    }
}
