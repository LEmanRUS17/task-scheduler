<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Domain\Repository;

use App\AuditLogFeature\Domain\Entity\AuditEntry;

interface AuditEntryRepositoryInterface
{
    public function save(AuditEntry $entry): void;

    /**
     * @param string[] $entityClasses restrict to these entity FQCNs; empty means no restriction
     * @return AuditEntry[]
     */
    public function findByActor(
        string $actorId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        int $limit,
        int $offset,
        array $entityClasses = [],
    ): array;

    /** @param string[] $entityClasses restrict to these entity FQCNs; empty means no restriction */
    public function countByActor(
        string $actorId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        array $entityClasses = [],
    ): int;

    /**
     * @param string[] $entityClasses restrict to these entity FQCNs; empty means no restriction
     * @return AuditEntry[]
     */
    public function findByActorInRange(
        string $actorId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        array $entityClasses = [],
    ): array;
}
