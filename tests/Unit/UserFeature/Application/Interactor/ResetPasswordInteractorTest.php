<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserFeature\Application\Interactor;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Interactor\ResetPasswordInteractor;
use App\UserFeature\Domain\Port\ClockInterface;
use App\UserFeature\Domain\Port\DomainEventDispatcherInterface;
use App\UserFeature\Domain\Port\PasswordHasherInterface;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class ResetPasswordInteractorTest extends TestCase
{
    private ClockInterface $clock;
    private PasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-01 12:00:00'));

        $this->hasher = $this->createStub(PasswordHasherInterface::class);
        $this->hasher->method('hash')->willReturn(HashedPassword::fromHash('new-hash'));
    }

    public function testResetAppliesNewPasswordWhenCodeIsValid(): void
    {
        $user = $this->userWithResetCode('654321');

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn($user);
        $repository->expects($this->once())->method('save')->with($user);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $interactor = new ResetPasswordInteractor($repository, $this->hasher, $dispatcher, $this->clock);
        $interactor->reset(Email::fromString('user@example.com'), '654321', 'new-pass');

        $this->assertSame('new-hash', $user->password()->value());
    }

    public function testResetThrowsWhenUserNotFound(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn(null);
        $repository->expects($this->never())->method('save');

        $interactor = new ResetPasswordInteractor(
            $repository,
            $this->hasher,
            $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );

        $this->expectException(\DomainException::class);

        $interactor->reset(Email::fromString('missing@example.com'), '654321', 'new-pass');
    }

    public function testResetThrowsOnInvalidCode(): void
    {
        $repository = $this->createStub(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn($this->userWithResetCode('654321'));

        $interactor = new ResetPasswordInteractor(
            $repository,
            $this->hasher,
            $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );

        $this->expectException(\DomainException::class);

        $interactor->reset(Email::fromString('user@example.com'), '000000', 'new-pass');
    }

    private function userWithResetCode(string $code): User
    {
        $user = User::register(
            UserId::generate(),
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('old-hash'),
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );
        $user->requestPasswordReset($code, new \DateTimeImmutable('2026-12-31 23:59:59'));
        $user->pullDomainEvents();

        return $user;
    }
}
