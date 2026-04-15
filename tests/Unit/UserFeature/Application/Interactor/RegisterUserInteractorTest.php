<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserFeature\Application\Interactor;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Interactor\RegisterUserInteractor;
use App\UserFeature\Domain\Port\ClockInterface;
use App\UserFeature\Domain\Port\DomainEventDispatcherInterface;
use App\UserFeature\Domain\Port\PasswordHasherInterface;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class RegisterUserInteractorTest extends TestCase
{
    private UserRepositoryInterface $repository;
    private PasswordHasherInterface $hasher;
    private DomainEventDispatcherInterface $dispatcher;
    private ClockInterface $clock;
    private RegisterUserInteractor $interactor;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->hasher = $this->createMock(PasswordHasherInterface::class);
        $this->dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);

        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));
        $this->hasher->method('hash')->willReturn(HashedPassword::fromHash('$2y$13$hashed'));

        $this->interactor = new RegisterUserInteractor(
            $this->repository,
            $this->hasher,
            $this->dispatcher,
            $this->clock,
        );
    }

    public function testRegisterSavesUser(): void
    {
        $this->repository->expects($this->once())->method('findByEmail')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $this->interactor->register(Email::fromString('user@example.com'), 'Password1');
    }

    public function testRegisterHashesPassword(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);
        $this->repository->method('save');

        $this->hasher
            ->expects($this->once())
            ->method('hash')
            ->with('Password1')
            ->willReturn(HashedPassword::fromHash('$2y$13$hashed'));

        $this->interactor->register(Email::fromString('user@example.com'), 'Password1');
    }

    public function testRegisterDispatchesUserRegisteredEvent(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);
        $this->repository->method('save');

        $this->dispatcher->expects($this->once())->method('dispatch');

        $this->interactor->register(Email::fromString('user@example.com'), 'Password1');
    }

    public function testRegisterThrowsWhenEmailAlreadyExists(): void
    {
        $existingUser = User::register(
            UserId::generate(),
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('hash'),
            new \DateTimeImmutable(),
        );

        $this->repository->method('findByEmail')->willReturn($existingUser);
        $this->repository->expects($this->never())->method('save');

        $this->expectException(\DomainException::class);

        $this->interactor->register(Email::fromString('user@example.com'), 'Password1');
    }
}
