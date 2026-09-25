<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Domain\Interactor;

use App\NotificationFeature\Domain\Entity\TelegramChat;
use App\NotificationFeature\Domain\Exception\TelegramChatNotLinkedException;
use App\NotificationFeature\Domain\Interactor\UnlinkTelegramChatInteractor;
use App\NotificationFeature\Domain\Notification\TelegramNotifierInterface;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use App\NotificationFeature\Domain\ValueObject\TelegramChatState;
use PHPUnit\Framework\TestCase;

final class UnlinkTelegramChatInteractorTest extends TestCase
{
    public function testUnlinkDeletesTheChatAndNotifiesIt(): void
    {
        $chat = TelegramChat::create('user-1', 12345, TelegramChatState::MainMenu);

        $telegramChats = $this->createMock(TelegramChatRepositoryInterface::class);
        $telegramChats->expects($this->once())->method('findByUserId')->with('user-1')->willReturn($chat);
        $telegramChats->expects($this->once())->method('delete')->with($chat);

        $telegramNotifier = $this->createMock(TelegramNotifierInterface::class);
        $telegramNotifier->expects($this->once())->method('notify')->with(12345, $this->isString());

        (new UnlinkTelegramChatInteractor($telegramChats, $telegramNotifier))->unlink('user-1');
    }

    public function testUnlinkThrowsWhenTheUserHasNoLinkedChat(): void
    {
        $telegramChats = $this->createMock(TelegramChatRepositoryInterface::class);
        $telegramChats->method('findByUserId')->willReturn(null);
        $telegramChats->expects($this->never())->method('delete');

        $telegramNotifier = $this->createMock(TelegramNotifierInterface::class);
        $telegramNotifier->expects($this->never())->method('notify');

        $this->expectException(TelegramChatNotLinkedException::class);

        (new UnlinkTelegramChatInteractor($telegramChats, $telegramNotifier))->unlink('user-1');
    }
}
