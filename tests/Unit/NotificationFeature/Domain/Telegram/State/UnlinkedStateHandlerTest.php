<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Domain\Telegram\State;

use App\NotificationFeature\Domain\Entity\TelegramChat;
use App\NotificationFeature\Domain\Notification\TelegramNotifierInterface;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use App\NotificationFeature\Domain\Repository\TelegramLinkTokenRepositoryInterface;
use App\NotificationFeature\Domain\Telegram\State\UnlinkedStateHandler;
use App\NotificationFeature\Domain\ValueObject\TelegramChatState;
use App\NotificationFeature\Domain\ValueObject\TelegramUpdate;
use PHPUnit\Framework\TestCase;

final class UnlinkedStateHandlerTest extends TestCase
{
    public function testLinksTheChatWhenTheStartTokenIsValid(): void
    {
        $tokens = $this->createMock(TelegramLinkTokenRepositoryInterface::class);
        $tokens->expects($this->once())->method('findUserIdByToken')->with('abc123')->willReturn('user-1');
        $tokens->expects($this->once())->method('delete')->with('abc123');

        $telegramChats = $this->createMock(TelegramChatRepositoryInterface::class);
        $telegramChats->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (TelegramChat $chat) => $chat->userId() === 'user-1'
                    && $chat->chatId() === 555
                    && $chat->state() === TelegramChatState::MainMenu,
            ));

        $notifier = $this->createMock(TelegramNotifierInterface::class);
        $notifier->expects($this->once())->method('notify')->with(555, $this->stringContains('linked'));

        $handler = new UnlinkedStateHandler($tokens, $telegramChats, $notifier);

        $handler->handle(new TelegramUpdate(555, '/start abc123'), null);
    }

    public function testAsksTheUserToRequestANewLinkWhenTheTokenIsUnknownOrExpired(): void
    {
        $tokens = $this->createMock(TelegramLinkTokenRepositoryInterface::class);
        $tokens->method('findUserIdByToken')->willReturn(null);
        $tokens->expects($this->never())->method('delete');

        $telegramChats = $this->createMock(TelegramChatRepositoryInterface::class);
        $telegramChats->expects($this->never())->method('save');

        $notifier = $this->createMock(TelegramNotifierInterface::class);
        $notifier->expects($this->once())->method('notify')->with(555, $this->stringContains('expired'));

        $handler = new UnlinkedStateHandler($tokens, $telegramChats, $notifier);

        $handler->handle(new TelegramUpdate(555, '/start unknown-token'), null);
    }

    public function testPromptsForTheConnectLinkWhenStartHasNoToken(): void
    {
        $tokens = $this->createMock(TelegramLinkTokenRepositoryInterface::class);
        $tokens->expects($this->never())->method('findUserIdByToken');

        $telegramChats = $this->createMock(TelegramChatRepositoryInterface::class);
        $telegramChats->expects($this->never())->method('save');

        $notifier = $this->createMock(TelegramNotifierInterface::class);
        $notifier->expects($this->once())->method('notify')->with(555, $this->stringContains('connect link'));

        $handler = new UnlinkedStateHandler($tokens, $telegramChats, $notifier);

        $handler->handle(new TelegramUpdate(555, '/start'), null);
    }

    public function testPromptsForTheConnectLinkWhenTheMessageHasNoText(): void
    {
        $tokens = $this->createMock(TelegramLinkTokenRepositoryInterface::class);
        $tokens->expects($this->never())->method('findUserIdByToken');

        $telegramChats = $this->createMock(TelegramChatRepositoryInterface::class);
        $telegramChats->expects($this->never())->method('save');

        $notifier = $this->createMock(TelegramNotifierInterface::class);
        $notifier->expects($this->once())->method('notify')->with(555, $this->stringContains('connect link'));

        $handler = new UnlinkedStateHandler($tokens, $telegramChats, $notifier);

        $handler->handle(new TelegramUpdate(555, null), null);
    }
}
