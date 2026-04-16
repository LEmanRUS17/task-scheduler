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
    private PasswordHasherInterface $hasher;
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));

        $this->hasher = $this->createStub(PasswordHasherInterface::class);
        $this->hasher->method('hash')->willReturn(HashedPassword::fromHash('$2y$13$hashed'));
    }

    private function buildInteractor(
        UserRepositoryInterface $repository,
        ?PasswordHasherInterface $hasher = null,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): RegisterUserInteractor {
        return new RegisterUserInteractor(
            $repository,
            $hasher ?? $this->hasher,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testRegisterSavesUser(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())->method('findByEmail')->willReturn(null);
        $repository->expects($this->once())->method('save');

        $this->buildInteractor($repository)->register(Email::fromString('user@example.com'), 'Password1');
    }

    public function testRegisterHashesPassword(): void
    {
        $repository = $this->createStub(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn(null);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hash')
            ->with('Password1')
            ->willReturn(HashedPassword::fromHash('$2y$13$hashed'));

        $this->buildInteractor($repository, $hasher)->register(Email::fromString('user@example.com'), 'Password1');
    }

    public function testRegisterDispatchesUserRegisteredEvent(): void
    {
        $repository = $this->createStub(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn(null);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $this->buildInteractor($repository, null, $dispatcher)->register(Email::fromString('user@example.com'), 'Password1');
    }

    public function testRegisterThrowsWhenEmailAlreadyExists(): void
    {
        $existingUser = User::register(
            UserId::generate(),
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('hash'),
            new \DateTimeImmutable(),
        );

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn($existingUser);
        $repository->expects($this->never())->method('save');

        $this->expectException(\DomainException::class);

        $this->buildInteractor($repository)->register(Email::fromString('user@example.com'), 'Password1');
    }
}
