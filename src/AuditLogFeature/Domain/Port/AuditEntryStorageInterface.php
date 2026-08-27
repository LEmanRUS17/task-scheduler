<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Domain\Port;

interface AuditEntryStorageInterface
{
    public function record(
        string $id,
        string $entityClass,
        string $entityId,
        string $action,
        string $changedData,
        string $actorId,
        \DateTimeImmutable $occurredAt,
        string $title,
    ): void;
}
