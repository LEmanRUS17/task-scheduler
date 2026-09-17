<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Domain\Interactor;

use App\NotificationFeature\Domain\Exception\TelegramChatAlreadyLinkedException;
use App\NotificationFeature\Domain\Interactor\CreateTelegramLinkTokenInteractor;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use App\NotificationFeature\Domain\Repository\TelegramLinkTokenRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CreateTelegramLinkTokenInteractorTest extends TestCase
{
    public function testCreateStoresAGeneratedTokenForTheUserWithAOneHourTtl(): void
    {
        $tokens = $this->createMock(TelegramLinkTokenRepositoryInterface::class);
        $tokens->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (mixed $token) => \is_string($token) && $token !== ''), 'user-1', 3600);

        $telegramChats = $this->createStub(TelegramChatRepositoryInterface::class);
        $telegramChats->method('isLinkedForUserId')->willReturn(false);

        $token = (new CreateTelegramLinkTokenInteractor($tokens, $telegramChats))->create('user-1');

        $this->assertNotSame('', $token);
    }

    public function testCreateReturnsADifferentTokenOnEachCall(): void
    {
        $tokens = $this->createStub(TelegramLinkTokenRepositoryInterface::class);
        $telegramChats = $this->createStub(TelegramChatRepositoryInterface::class);
        $telegramChats->method('isLinkedForUserId')->willReturn(false);

        $interactor = new CreateTelegramLinkTokenInteractor($tokens, $telegramChats);

        $this->assertNotSame($interactor->create('user-1'), $interactor->create('user-1'));
    }

    public function testCreateThrowsWhenTheUserAlreadyHasALinkedChat(): void
    {
        $tokens = $this->createMock(TelegramLinkTokenRepositoryInterface::class);
        $tokens->expects($this->never())->method('save');

        $telegramChats = $this->createStub(TelegramChatRepositoryInterface::class);
        $telegramChats->method('isLinkedForUserId')->willReturn(true);

        $this->expectException(TelegramChatAlreadyLinkedException::class);

        (new CreateTelegramLinkTokenInteractor($tokens, $telegramChats))->create('user-1');
    }
}
