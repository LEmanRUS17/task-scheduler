<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Messenger;

use App\NotificationFeature\Infrastructure\Messenger\Message\TelegramNotificationMessage;
use App\NotificationFeature\Infrastructure\Messenger\MessengerTelegramNotifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerTelegramNotifierTest extends TestCase
{
    public function testNotifyDispatchesTelegramNotificationMessage(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (TelegramNotificationMessage $message): bool {
                return $message->chatId === 1450870602 && $message->text === 'Hello from tests';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $notifier = new MessengerTelegramNotifier($bus);
        $notifier->notify(1450870602, 'Hello from tests');
    }
}
