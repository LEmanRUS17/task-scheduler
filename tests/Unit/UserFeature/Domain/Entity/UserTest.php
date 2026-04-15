<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserFeature\Domain\Entity;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Event\UserRegistered;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
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
}
