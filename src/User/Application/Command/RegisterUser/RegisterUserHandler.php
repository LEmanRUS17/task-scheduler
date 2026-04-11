<?php

declare(strict_types=1);

namespace App\User\Application\Command\RegisterUser;

use App\User\Application\Port\PasswordHasherInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(RegisterUserCommand $command): void
    {
        $email = Email::fromString($command->email);

        if ($this->users->findByEmail($email) !== null) {
            throw new \DomainException("User {$command->email} already exists");
        }

        $id = UserId::generate();
        $hashedPassword = $this->passwordHasher->hash($command->plainPassword);
        $user = User::register($id, $email, $hashedPassword);

        $this->users->save($user);
    }
}
