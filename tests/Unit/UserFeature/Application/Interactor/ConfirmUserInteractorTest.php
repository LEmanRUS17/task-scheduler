<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserFeature\Application\Interactor;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Interactor\ConfirmUserInteractor;
use App\UserFeature\Domain\Port\ClockInterface;
use App\UserFeature\Domain\Port\DomainEventDispatcherInterface;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Domain\ValueObject\UserStatus;
use PHPUnit\Framework\TestCase;

final class ConfirmUserInteractorTest extends TestCase
{
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-01 12:00:00'));
    }

    public function testConfirmActivatesAndSavesUser(): void
    {
        $user = $this->pendingUser('654321');

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn($user);
        $repository->expects($this->once())->method('save')->with($user);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $interactor = new ConfirmUserInteractor($repository, $dispatcher, $this->clock);
        $interactor->confirm(Email::fromString('user@example.com'), '654321');

        $this->assertSame(UserStatus::ACTIVE, $user->status());
    }

    public function testConfirmThrowsWhenUserNotFound(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn(null);
        $repository->expects($this->never())->method('save');

        $interactor = new ConfirmUserInteractor(
            $repository,
            $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );

        $this->expectException(\DomainException::class);

        $interactor->confirm(Email::fromString('missing@example.com'), '654321');
    }

    public function testConfirmThrowsOnInvalidCode(): void
    {
        $repository = $this->createStub(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn($this->pendingUser('654321'));

        $interactor = new ConfirmUserInteractor(
            $repository,
            $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );

        $this->expectException(\DomainException::class);

        $interactor->confirm(Email::fromString('user@example.com'), '000000');
    }

    private function pendingUser(string $code): User
    {
        return User::registerPending(
            UserId::generate(),
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('hash'),
            $code,
            new \DateTimeImmutable('2026-12-31 23:59:59'),
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );
    }
}
