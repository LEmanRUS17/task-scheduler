<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Exception;

final class TelegramChatNotLinkedException extends \DomainException
{
    public static function forUserId(string $userId): self
    {
        return new self(sprintf('User "%s" has no linked Telegram chat', $userId));
    }
}
