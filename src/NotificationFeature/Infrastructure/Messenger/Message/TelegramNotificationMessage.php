<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Message;

final class TelegramNotificationMessage
{
    public function __construct(
        public readonly int $chatId,
        public readonly string $text,
    ) {
    }
}
