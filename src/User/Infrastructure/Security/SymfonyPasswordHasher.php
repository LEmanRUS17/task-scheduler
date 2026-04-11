<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Application\Port\PasswordHasherInterface;
use App\User\Domain\ValueObject\HashedPassword;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private readonly PasswordHasherFactoryInterface $factory,
    ) {}

    public function hash(string $plainPassword): HashedPassword
    {
        $hash = $this->factory
            ->getPasswordHasher(SecurityUser::class)
            ->hash($plainPassword);

        return HashedPassword::fromHash($hash);
    }
}
