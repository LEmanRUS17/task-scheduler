<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Messenger\Handler;

use App\NotificationFeature\Domain\Interactor\HandleTelegramWebhookInteractor;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use App\NotificationFeature\Domain\Telegram\TelegramStateHandlerInterface;
use App\NotificationFeature\Domain\ValueObject\TelegramChatState;
use App\NotificationFeature\Domain\ValueObject\TelegramUpdate;
use App\NotificationFeature\Infrastructure\Messenger\Handler\TelegramUpdateHandler;
use App\NotificationFeature\Infrastructure\Messenger\Message\TelegramUpdateMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class TelegramUpdateHandlerTest extends TestCase
{
    private TelegramStateHandlerInterface&MockObject $unlinkedHandler;
    private TelegramUpdateHandler $handler;

    protected function setUp(): void
    {
        $telegramChats = $this->createStub(TelegramChatRepositoryInterface::class);
        $telegramChats->method('findByChatId')->willReturn(null);

        $this->unlinkedHandler = $this->createMock(TelegramStateHandlerInterface::class);
        $this->unlinkedHandler->method('state')->willReturn(TelegramChatState::Unlinked);

        $this->handler = new TelegramUpdateHandler(
            new HandleTelegramWebhookInteractor($telegramChats, [$this->unlinkedHandler]),
        );
    }

    public function testPassesUpdateToWebhookInteractor(): void
    {
        $this->unlinkedHandler->expects($this->once())
            ->method('handle')
            ->with(new TelegramUpdate(123, '/start token'), null);

        ($this->handler)(new TelegramUpdateMessage([
            'update_id' => 1,
            'message' => ['chat' => ['id' => 123], 'text' => '/start token'],
        ]));
    }

    public function testPassesCallbackQueryUpdateToWebhookInteractor(): void
    {
        $this->unlinkedHandler->expects($this->once())
            ->method('handle')
            ->with(new TelegramUpdate(123, 'menu'), null);

        ($this->handler)(new TelegramUpdateMessage([
            'update_id' => 2,
            'callback_query' => ['message' => ['chat' => ['id' => 123], 'text' => 'menu']],
        ]));
    }

    public function testIgnoresUpdateWithoutChat(): void
    {
        $this->unlinkedHandler->expects($this->never())->method('handle');

        ($this->handler)(new TelegramUpdateMessage(['update_id' => 3, 'edited_channel_post' => []]));
    }

    public function testFailureIsNotRetried(): void
    {
        $this->unlinkedHandler->expects($this->once())
            ->method('handle')
            ->willThrowException(new \RuntimeException('boom'));

        $this->expectException(UnrecoverableMessageHandlingException::class);
        $this->expectExceptionMessage('boom');

        ($this->handler)(new TelegramUpdateMessage(['message' => ['chat' => ['id' => 123], 'text' => 'hi']]));
    }
}
