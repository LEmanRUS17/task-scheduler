<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Domain\Interactor;

use App\NotificationFeature\Domain\Interactor\CheckTelegramLinkStatusInteractor;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CheckTelegramLinkStatusInteractorTest extends TestCase
{
    public function testIsLinkedReturnsTrueWhenTheUserHasALinkedChat(): void
    {
        $telegramChats = $this->createMock(TelegramChatRepositoryInterface::class);
        $telegramChats->expects($this->once())->method('isLinkedForUserId')->with('user-1')->willReturn(true);

        $interactor = new CheckTelegramLinkStatusInteractor($telegramChats);

        $this->assertTrue($interactor->isLinked('user-1'));
    }

    public function testIsLinkedReturnsFalseWhenTheUserHasNoLinkedChat(): void
    {
        $telegramChats = $this->createMock(TelegramChatRepositoryInterface::class);
        $telegramChats->expects($this->once())->method('isLinkedForUserId')->with('user-1')->willReturn(false);

        $interactor = new CheckTelegramLinkStatusInteractor($telegramChats);

        $this->assertFalse($interactor->isLinked('user-1'));
    }
}
