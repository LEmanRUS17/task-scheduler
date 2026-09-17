<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Interactor;

use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;

final class CheckTelegramLinkStatusInteractor
{
    public function __construct(
        private readonly TelegramChatRepositoryInterface $telegramChats,
    ) {
    }

    public function isLinked(string $userId): bool
    {
        return $this->telegramChats->isLinkedForUserId($userId);
    }
}
