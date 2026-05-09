<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Message;

use App\NotificationFeature\Domain\Notification\NotificationActionInterface;

final class NotificationDispatchMessage
{
    public function __construct(
        public readonly string $id,
        public readonly string $event,
        public readonly \DateTimeImmutable $occurredAt,
        public readonly NotificationActionInterface $action,
    ) {}

    public static function create(string $event, NotificationActionInterface $action): self
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return new self(
            id: vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)),
            event: $event,
            occurredAt: new \DateTimeImmutable(),
            action: $action,
        );
    }
}
