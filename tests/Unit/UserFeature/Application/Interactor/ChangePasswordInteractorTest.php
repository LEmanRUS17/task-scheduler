<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserFeature\Application\Interactor;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Interactor\ChangePasswordInteractor;
use App\UserFeature\Domain\Port\ClockInterface;
use App\UserFeature\Domain\Port\DomainEventDispatcherInterface;
use App\UserFeature\Domain\Port\PasswordHasherInterface;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class ChangePasswordInteractorTest extends TestCase
{
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-01 12:00:00'));
    }

    public function testChangePasswordHashesNewPasswordAndSavesUser(): void
    {
        $user = $this->activeUser();

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findById')->willReturn($user);
        $repository->expects($this->once())->method('save')->with($user);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())->method('verify')->with($this->anything(), 'current-pass')->willReturn(true);
        $hasher->expects($this->once())
            ->method('hash')
            ->with('new-pass')
            ->willReturn(HashedPassword::fromHash('new-hash'));

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $interactor = new ChangePasswordInteractor($repository, $hasher, $dispatcher, $this->clock);
        $interactor->changePassword($user->id(), 'current-pass', 'new-pass');

        $this->assertSame('new-hash', $user->password()->value());
        $this->assertEquals(new \DateTimeImmutable('2026-01-01 12:00:00'), $user->passwordUpdatedAt());
    }

    public function testChangePasswordThrowsWhenUserNotFound(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);
        $repository->expects($this->never())->method('save');

        $interactor = new ChangePasswordInteractor(
            $repository,
            $this->createStub(PasswordHasherInterface::class),
            $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );

        $this->expectException(\DomainException::class);

        $interactor->changePassword(UserId::generate(), 'current-pass', 'new-pass');
    }

    public function testChangePasswordThrowsWhenCurrentPasswordIsWrong(): void
    {
        $user = $this->activeUser();

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findById')->willReturn($user);
        $repository->expects($this->never())->method('save');

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(false);

        $interactor = new ChangePasswordInteractor(
            $repository,
            $hasher,
            $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );

        $this->expectException(\DomainException::class);

        $interactor->changePassword($user->id(), 'wrong-pass', 'new-pass');
    }

    private function activeUser(): User
    {
        return User::register(
            UserId::generate(),
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('old-hash'),
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );
    }
}
