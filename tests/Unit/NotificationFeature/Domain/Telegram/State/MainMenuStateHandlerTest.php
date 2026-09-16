<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Domain\Telegram\State;

use App\NotificationFeature\Domain\Entity\TelegramChat;
use App\NotificationFeature\Domain\Notification\TelegramNotifierInterface;
use App\NotificationFeature\Domain\Telegram\State\MainMenuStateHandler;
use App\NotificationFeature\Domain\ValueObject\TelegramChatState;
use App\NotificationFeature\Domain\ValueObject\TelegramUpdate;
use PHPUnit\Framework\TestCase;

final class MainMenuStateHandlerTest extends TestCase
{
    public function testStateIsMainMenu(): void
    {
        $notifier = $this->createStub(TelegramNotifierInterface::class);

        $this->assertSame(TelegramChatState::MainMenu, (new MainMenuStateHandler($notifier))->state());
    }

    public function testAcknowledgesTheAlreadyLinkedChat(): void
    {
        $chat = TelegramChat::create('user-1', 555, TelegramChatState::MainMenu);

        $notifier = $this->createMock(TelegramNotifierInterface::class);
        $notifier->expects($this->once())->method('notify')->with(555, $this->stringContains('already linked'));

        (new MainMenuStateHandler($notifier))->handle(new TelegramUpdate(555, 'hi'), $chat);
    }
}
