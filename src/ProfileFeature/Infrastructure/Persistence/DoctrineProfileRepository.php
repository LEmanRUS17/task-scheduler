<?php

declare(strict_types=1);

namespace App\ProfileFeature\Infrastructure\Persistence;

use App\ProfileFeature\Domain\Entity\Profile;
use App\ProfileFeature\Domain\Repository\ProfileRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineProfileRepository implements ProfileRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function save(Profile $profile): void
    {
        $this->entityManager->persist($profile);
        $this->entityManager->flush();
    }

    public function findByUserId(string $userId): ?Profile
    {
        return $this->entityManager->find(Profile::class, $userId);
    }
}
