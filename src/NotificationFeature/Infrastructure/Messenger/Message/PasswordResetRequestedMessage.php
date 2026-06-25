<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Message;

final class PasswordResetRequestedMessage
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $resetCode,
    ) {
    }
}
