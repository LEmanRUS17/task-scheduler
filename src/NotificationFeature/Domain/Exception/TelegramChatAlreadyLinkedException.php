<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Exception;

final class TelegramChatAlreadyLinkedException extends \DomainException
{
    public static function forUserId(string $userId): self
    {
        return new self(sprintf('User "%s" already has a linked Telegram chat', $userId));
    }
}
