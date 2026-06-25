<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Interactor;

use App\UserFeature\Domain\Port\ClockInterface;
use App\UserFeature\Domain\Port\DomainEventDispatcherInterface;
use App\UserFeature\Domain\Port\PasswordHasherInterface;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;

final class ResetPasswordInteractor
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @throws \DomainException when the user is missing or the reset code is
     *                          invalid/expired (the message stays generic so
     *                          the endpoint does not disclose which emails exist).
     */
    public function reset(Email $email, string $code, string $newPassword): void
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            throw new \DomainException('Invalid reset code');
        }

        $user->resetPassword($code, $this->passwordHasher->hash($newPassword), $this->clock->now());

        $this->users->save($user);

        $this->eventDispatcher->dispatch(...$user->pullDomainEvents());
    }
}
