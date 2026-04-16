<?php

declare(strict_types=1);

namespace App\TeamFeature\Infrastructure\Persistence;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTeamRepository implements TeamRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function save(Team $team): void
    {
        $this->entityManager->persist($team);
        $this->entityManager->flush();
    }

    public function findById(TeamId $id): ?Team
    {
        return $this->entityManager->find(Team::class, $id->value());
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Team::class)->findAll();
    }

    public function delete(Team $team): void
    {
        $this->entityManager->remove($team);
        $this->entityManager->flush();
    }
}
