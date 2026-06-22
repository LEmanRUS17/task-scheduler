<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserFeature\Domain\Entity;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Event\UserConfirmed;
use App\UserFeature\Domain\Event\UserRegistered;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Domain\ValueObject\UserStatus;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testRegisterCreatesUserWithCorrectData(): void
    {
        $id = UserId::generate();
        $email = Email::fromString('user@example.com');
        $password = HashedPassword::fromHash('$2y$13$somehash');
        $createdAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        $user = User::register($id, $email, $password, $createdAt);

        $this->assertSame($id->value(), $user->id()->value());
        $this->assertSame('user@example.com', $user->email()->value());
        $this->assertSame('$2y$13$somehash', $user->password()->value());
        $this->assertContains('ROLE_USER', $user->roles());
    }

    public function testRegisterDispatchesUserRegisteredEvent(): void
    {
        $user = User::register(
            UserId::generate(),
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('hash'),
            new \DateTimeImmutable(),
        );

        $events = $user->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserRegistered::class, $events[0]);
    }

    public function testUserRegisteredEventContainsCorrectData(): void
    {
        $id = UserId::generate();
        $email = Email::fromString('user@example.com');

        $user = User::register($id, $email, HashedPassword::fromHash('hash'), new \DateTimeImmutable());

        /** @var UserRegistered $event */
        $event = $user->pullDomainEvents()[0];

        $this->assertSame($id->value(), $event->userId->value());
        $this->assertSame('user@example.com', $event->email->value());
    }

    public function testPullDomainEventsClearsQueue(): void
    {
        $user = User::register(
            UserId::generate(),
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('hash'),
            new \DateTimeImmutable(),
        );

        $user->pullDomainEvents();

        $this->assertEmpty($user->pullDomainEvents());
    }

    public function testRegisterPendingCreatesPendingUserAndCarriesCodeOnEvent(): void
    {
        $user = $this->pendingUser('654321');

        $this->assertSame(UserStatus::PENDING, $user->status());

        /** @var UserRegistered $event */
        $event = $user->pullDomainEvents()[0];
        $this->assertInstanceOf(UserRegistered::class, $event);
        $this->assertSame('654321', $event->confirmationCode);
    }

    public function testConfirmActivatesUserWithValidCode(): void
    {
        $user = $this->pendingUser('654321');
        $user->pullDomainEvents();

        $user->confirm('654321', new \DateTimeImmutable('2026-01-01 12:00:00'));

        $this->assertSame(UserStatus::ACTIVE, $user->status());

        /** @var UserConfirmed $event */
        $event = $user->pullDomainEvents()[0];
        $this->assertInstanceOf(UserConfirmed::class, $event);
    }

    public function testConfirmRejectsInvalidCode(): void
    {
        $user = $this->pendingUser('654321');

        $this->expectException(\DomainException::class);

        $user->confirm('000000', new \DateTimeImmutable('2026-01-01 12:00:00'));
    }

    public function testConfirmRejectsExpiredCode(): void
    {
        $user = User::registerPending(
            UserId::generate(),
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('hash'),
            '654321',
            new \DateTimeImmutable('2026-01-01 12:00:00'),
            new \DateTimeImmutable('2026-01-01 11:00:00'),
        );

        $this->expectException(\DomainException::class);

        $user->confirm('654321', new \DateTimeImmutable('2026-01-02 12:00:00'));
    }

    public function testConfirmRejectsAlreadyConfirmedUser(): void
    {
        $user = $this->pendingUser('654321');
        $user->confirm('654321', new \DateTimeImmutable('2026-01-01 12:00:00'));

        $this->expectException(\DomainException::class);

        $user->confirm('654321', new \DateTimeImmutable('2026-01-01 12:00:00'));
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
