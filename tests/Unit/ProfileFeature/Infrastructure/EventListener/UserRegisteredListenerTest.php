<?php

declare(strict_types=1);

namespace App\Tests\Unit\ProfileFeature\Infrastructure\EventListener;

use App\ProfileFeature\Infrastructure\EventListener\UserRegisteredListener;
use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\UserFeature\Domain\Event\UserRegistered;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class UserRegisteredListenerTest extends TestCase
{
    public function testInvokeCallsCreateForUserWithUserId(): void
    {
        $userId = UserId::generate();
        $user = \App\UserFeature\Domain\Entity\User::register(
            $userId,
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('hash'),
            new \DateTimeImmutable(),
        );

        /** @var UserRegistered $event */
        $event = $user->pullDomainEvents()[0];

        $profileService = $this->createMock(ProfileServiceInterface::class);
        $profileService->expects($this->once())
            ->method('createForUser')
            ->with($userId->value());

        (new UserRegisteredListener($profileService))($event);
    }
}
