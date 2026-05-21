<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Notification;

final class MessageAction implements NotificationActionInterface
{
    public const string TYPE = 'message';

    public function __construct(
        public readonly string $channel,
        public readonly string $recipient,
        public readonly string $subject,
        public readonly string $body,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => self::TYPE,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'body' => $this->body,
        ];
    }
}
