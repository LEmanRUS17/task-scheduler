<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entity_manager,
    ) {}

    public function save(User $user): void
    {
        $this->entity_manager->persist($user);
        $this->entity_manager->flush();
    }

    public function findById(UserId $id): ?User
    {
        return $this->entity_manager->find(User::class, $id->value());
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->entity_manager->getRepository(User::class)
            ->findOneBy(['email' => $email->value()]);
    }
}
