<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Notification;

interface TelegramNotifierInterface
{
    public function notify(int $chatId, string $text): void;
}
