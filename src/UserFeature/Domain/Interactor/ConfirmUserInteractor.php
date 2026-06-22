<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Interactor;

use App\UserFeature\Domain\Port\ClockInterface;
use App\UserFeature\Domain\Port\DomainEventDispatcherInterface;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;

final class ConfirmUserInteractor
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function confirm(Email $email, string $code): void
    {
        $user = $this->users->findByEmail($email);

        // A generic error keeps the endpoint from disclosing which emails exist.
        if ($user === null) {
            throw new \DomainException('Invalid confirmation code');
        }

        $user->confirm($code, $this->clock->now());

        $this->users->save($user);

        $this->eventDispatcher->dispatch(...$user->pullDomainEvents());
    }
}
