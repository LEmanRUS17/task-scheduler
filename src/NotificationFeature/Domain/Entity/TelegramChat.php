<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Entity;

final class TelegramChat
{
    private string $userId;
    private int $chatId;
    private int $state;

    public function __construct(string $userId, int $chatId, int $state = 0)
    {
        $this->userId = $userId;
        $this->chatId = $chatId;
        $this->state = $state;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function chatId(): int
    {
        return $this->chatId;
    }

    public function state(): int
    {
        return $this->state;
    }
}
