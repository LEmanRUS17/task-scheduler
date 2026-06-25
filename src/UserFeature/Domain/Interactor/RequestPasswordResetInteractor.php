<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Interactor;

use App\UserFeature\Domain\Port\ClockInterface;
use App\UserFeature\Domain\Port\ConfirmationCodeGeneratorInterface;
use App\UserFeature\Domain\Port\DomainEventDispatcherInterface;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;

final class RequestPasswordResetInteractor
{
    /**
     * Reset code lifespan.
     */
    private const string CODE_TTL = 'PT1H';

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
        private readonly ConfirmationCodeGeneratorInterface $codeGenerator,
    ) {
    }

    public function request(Email $email): void
    {
        $user = $this->users->findByEmail($email);

        // Silently ignore unknown emails so the endpoint cannot be used to
        // probe which accounts exist.
        if ($user === null) {
            return;
        }

        $now = $this->clock->now();
        $user->requestPasswordReset(
            $this->codeGenerator->generate(),
            $now->add(new \DateInterval(self::CODE_TTL)),
        );

        $this->users->save($user);

        $this->eventDispatcher->dispatch(...$user->pullDomainEvents());
    }
}
