<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\ValueObject;

final class TelegramUpdate
{
    public function __construct(
        public readonly int $chatId,
        public readonly ?string $text,
    ) {
    }

    /**
     * Reads chat id and text the way Telegram's webhook body carries them, for both
     * a plain message and a callback query (see DefaultController::actionWebhook in
     * the job reference implementation).
     *
     * @param array<string, mixed> $payload
     */
    public static function fromWebhookPayload(array $payload): ?self
    {
        $message = $payload['message'] ?? $payload['callback_query']['message'] ?? null;

        if (!\is_array($message) || !\is_array($message['chat'] ?? null)) {
            return null;
        }

        $chatId = $message['chat']['id'] ?? null;

        if (!\is_int($chatId) && !\is_string($chatId)) {
            return null;
        }

        $text = $message['text'] ?? null;

        return new self((int) $chatId, \is_string($text) ? $text : null);
    }
}
