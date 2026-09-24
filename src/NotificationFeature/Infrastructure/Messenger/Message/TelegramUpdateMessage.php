<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Message;

final class TelegramUpdateMessage
{
    /**
     * @param array<string, mixed> $payload a single Telegram Update object, the same body
     *                                      Telegram would POST to the bot's webhook
     */
    public function __construct(
        public readonly array $payload,
    ) {
    }
}
