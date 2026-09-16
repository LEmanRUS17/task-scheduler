<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Domain\Interactor;

use App\NotificationFeature\Domain\Entity\TelegramChat;
use App\NotificationFeature\Domain\Interactor\HandleTelegramWebhookInteractor;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use App\NotificationFeature\Domain\Telegram\TelegramStateHandlerInterface;
use App\NotificationFeature\Domain\ValueObject\TelegramChatState;
use App\NotificationFeature\Domain\ValueObject\TelegramUpdate;
use PHPUnit\Framework\TestCase;

final class HandleTelegramWebhookInteractorTest extends TestCase
{
    public function testDispatchesToTheUnlinkedHandlerWhenNoChatIsLinked(): void
    {
        $telegramChats = $this->createStub(TelegramChatRepositoryInterface::class);
        $telegramChats->method('findByChatId')->willReturn(null);

        $update = new TelegramUpdate(123, '/start token');

        $unlinkedHandler = $this->createMock(TelegramStateHandlerInterface::class);
        $unlinkedHandler->method('state')->willReturn(TelegramChatState::Unlinked);
        $unlinkedHandler->expects($this->once())->method('handle')->with($update, null);

        $mainMenuHandler = $this->createMock(TelegramStateHandlerInterface::class);
        $mainMenuHandler->method('state')->willReturn(TelegramChatState::MainMenu);
        $mainMenuHandler->expects($this->never())->method('handle');

        $interactor = new HandleTelegramWebhookInteractor($telegramChats, [$unlinkedHandler, $mainMenuHandler]);

        $interactor->handle($update);
    }

    public function testDispatchesToTheHandlerMatchingTheLinkedChatsPersistedState(): void
    {
        $chat = TelegramChat::create('user-1', 123, TelegramChatState::MainMenu);

        $telegramChats = $this->createStub(TelegramChatRepositoryInterface::class);
        $telegramChats->method('findByChatId')->willReturn($chat);

        $update = new TelegramUpdate(123, 'hello');

        $unlinkedHandler = $this->createMock(TelegramStateHandlerInterface::class);
        $unlinkedHandler->method('state')->willReturn(TelegramChatState::Unlinked);
        $unlinkedHandler->expects($this->never())->method('handle');

        $mainMenuHandler = $this->createMock(TelegramStateHandlerInterface::class);
        $mainMenuHandler->method('state')->willReturn(TelegramChatState::MainMenu);
        $mainMenuHandler->expects($this->once())->method('handle')->with($update, $chat);

        $interactor = new HandleTelegramWebhookInteractor($telegramChats, [$unlinkedHandler, $mainMenuHandler]);

        $interactor->handle($update);
    }

    public function testThrowsWhenNoHandlerIsRegisteredForTheResolvedState(): void
    {
        $telegramChats = $this->createStub(TelegramChatRepositoryInterface::class);
        $telegramChats->method('findByChatId')->willReturn(null);

        $interactor = new HandleTelegramWebhookInteractor($telegramChats, []);

        $this->expectException(\DomainException::class);

        $interactor->handle(new TelegramUpdate(123, '/start token'));
    }
}
