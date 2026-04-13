<?php

declare(strict_types=1);

namespace App\UserFeature\Infrastructure\Security;

use App\UserFeature\Domain\Port\PasswordHasherInterface;
use App\UserFeature\Domain\ValueObject\HashedPassword;
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
