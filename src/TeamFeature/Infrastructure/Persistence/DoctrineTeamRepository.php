<?php

declare(strict_types=1);

namespace App\TeamFeature\Infrastructure\Persistence;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTeamRepository implements TeamRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Team $team): void
    {
        $this->entityManager->persist($team);
        $this->entityManager->flush();
    }

    public function findById(TeamId $id): ?Team
    {
        return $this->entityManager->find(Team::class, $id->value());
    }

    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->entityManager->getRepository(Team::class)->findBy(['id' => $ids]);
    }

    public function findPaginatedByMemberUserId(string $userId, int $limit, int $offset): array
    {
        return $this->entityManager->createQuery(
            'SELECT t FROM ' . Team::class . ' t
             JOIN ' . TeamMember::class . ' tm WITH tm.teamId = t.id
             WHERE tm.userId = :userId
             ORDER BY t.createdAt DESC',
        )
            ->setParameter('userId', $userId)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getResult();
    }

    public function countByMemberUserId(string $userId): int
    {
        return (int) $this->entityManager->createQuery(
            'SELECT COUNT(DISTINCT t.id) FROM ' . Team::class . ' t
             JOIN ' . TeamMember::class . ' tm WITH tm.teamId = t.id
             WHERE tm.userId = :userId',
        )
            ->setParameter('userId', $userId)
            ->getSingleScalarResult();
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
