<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Interactor;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Port\ClockInterface;
use App\UserFeature\Domain\Port\ConfirmationCodeGeneratorInterface;
use App\UserFeature\Domain\Port\DomainEventDispatcherInterface;
use App\UserFeature\Domain\Port\PasswordHasherInterface;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\UserId;

final class RegisterUserInteractor
{
    /**
     * Code lifespan
     */
    private const string CODE_TTL = 'PT1H';

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
        private readonly ConfirmationCodeGeneratorInterface $codeGenerator,
    ) {
    }

    public function register(Email $email, string $plainPassword): void
    {
        if ($this->users->findByEmail($email) !== null) {
            throw new \DomainException("User {$email->value()} already exists");
        }

        $id = UserId::generate();
        $hashedPassword = $this->passwordHasher->hash($plainPassword);
        $now = $this->clock->now();
        $code = $this->codeGenerator->generate();

        $user = User::registerPending(
            $id,
            $email,
            $hashedPassword,
            $code,
            $now->add(new \DateInterval(self::CODE_TTL)),
            $now,
        );

        $this->users->save($user);

        $this->eventDispatcher->dispatch(...$user->pullDomainEvents());
    }
}
