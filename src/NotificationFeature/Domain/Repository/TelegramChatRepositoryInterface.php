<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Repository;

use App\NotificationFeature\Domain\Entity\TelegramChat;

interface TelegramChatRepositoryInterface
{
    public function findByUserId(string $userId): ?TelegramChat;

    public function findByChatId(int $chatId): ?TelegramChat;

    public function isLinkedForUserId(string $userId): bool;

    public function save(TelegramChat $chat): void;
}
