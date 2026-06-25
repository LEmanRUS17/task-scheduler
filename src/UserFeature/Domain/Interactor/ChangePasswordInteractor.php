<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Interactor;

use App\UserFeature\Domain\Port\ClockInterface;
use App\UserFeature\Domain\Port\DomainEventDispatcherInterface;
use App\UserFeature\Domain\Port\PasswordHasherInterface;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\UserId;

final class ChangePasswordInteractor
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Replaces the user's password after checking the supplied current one.
     *
     * @throws \DomainException when the user is missing or the current
     *                          password does not match the stored hash.
     */
    public function changePassword(UserId $userId, string $currentPassword, string $newPassword): void
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new \DomainException('User not found');
        }

        if (!$this->passwordHasher->verify($user->password(), $currentPassword)) {
            throw new \DomainException('Current password is incorrect');
        }

        $user->changePassword($this->passwordHasher->hash($newPassword), $this->clock->now());

        $this->users->save($user);

        $this->eventDispatcher->dispatch(...$user->pullDomainEvents());
    }
}
