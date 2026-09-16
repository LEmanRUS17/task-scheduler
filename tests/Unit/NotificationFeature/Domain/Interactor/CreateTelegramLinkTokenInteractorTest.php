<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Domain\Interactor;

use App\NotificationFeature\Domain\Interactor\CreateTelegramLinkTokenInteractor;
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

        $token = (new CreateTelegramLinkTokenInteractor($tokens))->create('user-1');

        $this->assertNotSame('', $token);
    }

    public function testCreateReturnsADifferentTokenOnEachCall(): void
    {
        $tokens = $this->createStub(TelegramLinkTokenRepositoryInterface::class);
        $interactor = new CreateTelegramLinkTokenInteractor($tokens);

        $this->assertNotSame($interactor->create('user-1'), $interactor->create('user-1'));
    }
}
