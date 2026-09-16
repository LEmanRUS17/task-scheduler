<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Entity;

use App\NotificationFeature\Domain\ValueObject\TelegramChatState;

final class TelegramChat
{
    private string $userId;
    private int $chatId;
    private int $state;

    private function __construct(string $userId, int $chatId, TelegramChatState $state)
    {
        $this->userId = $userId;
        $this->chatId = $chatId;
        $this->state = $state->value;
    }

    public static function create(string $userId, int $chatId, TelegramChatState $state): self
    {
        return new self($userId, $chatId, $state);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function chatId(): int
    {
        return $this->chatId;
    }

    public function state(): TelegramChatState
    {
        return TelegramChatState::from($this->state);
    }

    public function changeState(TelegramChatState $state): void
    {
        $this->state = $state->value;
    }
}
