<?php

declare(strict_types=1);

namespace App\UserFeature\Infrastructure\Persistence;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function findById(UserId $id): ?User
    {
        return $this->entityManager->find(User::class, $id->value());
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email->value()]);
    }
}
