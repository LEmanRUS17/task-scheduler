<?php

declare(strict_types=1);

namespace App\TagFeature\Infrastructure\Persistence;

use App\TagFeature\Domain\Entity\TagAssignment;
use App\TagFeature\Domain\Repository\TagAssignmentRepositoryInterface;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TaggableType;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTagAssignmentRepository implements TagAssignmentRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(TagAssignment $assignment): void
    {
        $this->entityManager->persist($assignment);
        $this->entityManager->flush();
    }

    public function delete(TagAssignment $assignment): void
    {
        $this->entityManager->remove($assignment);
        $this->entityManager->flush();
    }

    public function find(TagId $tagId, TaggableType $entityType, string $entityId): ?TagAssignment
    {
        return $this->entityManager->getRepository(TagAssignment::class)->findOneBy([
            'tagId' => $tagId->value(),
            'entityType' => $entityType->value(),
            'entityId' => $entityId,
        ]);
    }

    public function findByEntity(TaggableType $entityType, string $entityId): array
    {
        return $this->entityManager->getRepository(TagAssignment::class)->findBy([
            'entityType' => $entityType->value(),
            'entityId' => $entityId,
        ]);
    }

    public function findByTag(TagId $tagId): array
    {
        return $this->entityManager->getRepository(TagAssignment::class)->findBy([
            'tagId' => $tagId->value(),
        ]);
    }

    public function findTagIdsByEntityIds(TaggableType $entityType, array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        /** @var list<array{tagId: string}> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT a.tagId AS tagId')
            ->from(TagAssignment::class, 'a')
            ->where('a.entityType = :type')
            ->andWhere('a.entityId IN (:ids)')
            ->setParameter('type', $entityType->value())
            ->setParameter('ids', $entityIds)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row) => $row['tagId'], $rows);
    }

    public function findEntityIdsByTag(TaggableType $entityType, TagId $tagId): array
    {
        /** @var list<array{entityId: string}> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT a.entityId AS entityId')
            ->from(TagAssignment::class, 'a')
            ->where('a.entityType = :type')
            ->andWhere('a.tagId = :tagId')
            ->setParameter('type', $entityType->value())
            ->setParameter('tagId', $tagId->value())
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row) => $row['entityId'], $rows);
    }
}
