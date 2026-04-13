<?php

declare(strict_types=1);

namespace App\ProfileFeature\Infrastructure\Persistence;

use App\ProfileFeature\Domain\Entity\Profile;
use App\ProfileFeature\Domain\Repository\ProfileRepositoryInterface;
use App\ProfileFeature\Domain\ValueObject\ProfileId;
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

    public function findById(ProfileId $id): ?Profile
    {
        return $this->entityManager->find(Profile::class, $id->value());
    }

    public function findByUserId(string $userId): ?Profile
    {
        return $this->entityManager->getRepository(Profile::class)
            ->findOneBy(['userId' => $userId]);
    }
}
