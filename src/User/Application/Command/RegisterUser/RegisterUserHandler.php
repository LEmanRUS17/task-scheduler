<?php

declare(strict_types=1);

namespace App\User\Application\Command\RegisterUser;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface    $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function __invoke(RegisterUserCommand $command): void
    {
        $email = Email::fromString($command->email);

        if ($this->users->findByEmail($email) !== null) {
            throw new \DomainException("User {$command->email} already exists");
        }

        $id   = UserId::generate();
        $user = User::register($id, $email, HashedPassword::fromHash(''));

        $hash = $this->hasher->hashPassword(new SecurityUser($user), $command->plainPassword);

        $user = User::register($id, $email, HashedPassword::fromHash($hash));

        $this->users->save($user);
    }
}
