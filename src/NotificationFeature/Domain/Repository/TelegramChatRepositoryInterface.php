<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Repository;

use App\NotificationFeature\Domain\Entity\TelegramChat;

interface TelegramChatRepositoryInterface
{
    public function findByUserId(string $userId): ?TelegramChat;
}
