<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Infrastructure\Persistence;

use App\AuditLogFeature\Domain\Entity\AuditEntry;
use App\AuditLogFeature\Domain\Repository\AuditEntryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class DoctrineAuditEntryRepository implements AuditEntryRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(AuditEntry $entry): void
    {
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    public function findByActor(
        string $actorId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        int $limit,
        int $offset,
        array $entityClasses = [],
    ): array {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(AuditEntry::class, 'a')
            ->where('a.actorId = :actorId')
            ->setParameter('actorId', $actorId)
            ->orderBy('a.occurredAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyDateRange($qb, $from, $to);
        $this->applyEntityClasses($qb, $entityClasses);

        return $qb->getQuery()->getResult();
    }

    public function countByActor(
        string $actorId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        array $entityClasses = [],
    ): int {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(AuditEntry::class, 'a')
            ->where('a.actorId = :actorId')
            ->setParameter('actorId', $actorId);

        $this->applyDateRange($qb, $from, $to);
        $this->applyEntityClasses($qb, $entityClasses);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findByActorInRange(
        string $actorId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        array $entityClasses = [],
    ): array {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(AuditEntry::class, 'a')
            ->where('a.actorId = :actorId')
            ->andWhere('a.occurredAt >= :from')
            ->andWhere('a.occurredAt <= :to')
            ->orderBy('a.occurredAt', 'ASC')
            ->setParameter('actorId', $actorId)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $this->applyEntityClasses($qb, $entityClasses);

        return $qb->getQuery()->getResult();
    }

    private function applyDateRange(
        QueryBuilder $qb,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): void {
        if ($from !== null) {
            $qb->andWhere('a.occurredAt >= :from')->setParameter('from', $from);
        }

        if ($to !== null) {
            $qb->andWhere('a.occurredAt <= :to')->setParameter('to', $to);
        }
    }

    /** @param string[] $entityClasses */
    private function applyEntityClasses(QueryBuilder $qb, array $entityClasses): void
    {
        if ($entityClasses !== []) {
            $qb->andWhere('a.entityClass IN (:entityClasses)')->setParameter('entityClasses', $entityClasses);
        }
    }
}
