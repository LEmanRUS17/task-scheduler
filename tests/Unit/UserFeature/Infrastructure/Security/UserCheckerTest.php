<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserFeature\Infrastructure\Security;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Domain\ValueObject\UserStatus;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\UserFeature\Infrastructure\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserCheckerTest extends TestCase
{
    private UserChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new UserChecker();
    }

    public function testAllowsActiveUser(): void
    {
        $this->expectNotToPerformAssertions();

        $this->checker->checkPreAuth(new SecurityUser($this->makeUser()));
    }

    public function testRejectsDeletedUser(): void
    {
        $user = $this->makeUser();
        $this->setPrivate($user, 'status', UserStatus::DELETED);

        $this->expectException(CustomUserMessageAccountStatusException::class);

        $this->checker->checkPreAuth(new SecurityUser($user));
    }

    public function testRejectsPendingUser(): void
    {
        $user = $this->makeUser();
        $this->setPrivate($user, 'status', UserStatus::PENDING);

        $this->expectException(CustomUserMessageAccountStatusException::class);

        $this->checker->checkPreAuth(new SecurityUser($user));
    }

    public function testRejectsSoftDeletedUser(): void
    {
        $user = $this->makeUser();
        $this->setPrivate($user, 'deletedAt', new \DateTimeImmutable('2026-01-01'));

        $this->expectException(CustomUserMessageAccountStatusException::class);

        $this->checker->checkPreAuth(new SecurityUser($user));
    }

    public function testIgnoresNonSecurityUser(): void
    {
        $this->expectNotToPerformAssertions();

        $this->checker->checkPreAuth($this->createStub(UserInterface::class));
    }

    private function makeUser(): User
    {
        return User::register(
            UserId::fromString('a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d'),
            Email::fromString('checker@example.com'),
            HashedPassword::fromHash('$2y$04$dummyhashfortestingpurposesonly123456'),
            new \DateTimeImmutable(),
        );
    }

    private function setPrivate(User $user, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty(User::class, $property);
        $reflection->setValue($user, $value);
    }
}
