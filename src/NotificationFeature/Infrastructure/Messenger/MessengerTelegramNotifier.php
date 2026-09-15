<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger;

use App\NotificationFeature\Domain\Notification\TelegramNotifierInterface;
use App\NotificationFeature\Infrastructure\Messenger\Message\TelegramNotificationMessage;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerTelegramNotifier implements TelegramNotifierInterface
{
    public function __construct(
        private readonly MessageBusInterface $defaultBus,
    ) {
    }

    public function notify(int $chatId, string $text): void
    {
        $this->defaultBus->dispatch(new TelegramNotificationMessage(
            chatId: $chatId,
            text: $text,
        ));
    }
}
