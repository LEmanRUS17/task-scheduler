<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Infrastructure\Messenger\Message;

final class RecordAuditEntryMessage
{
    public function __construct(
        public readonly string $id,
        public readonly string $entityClass,
        public readonly string $entityId,
        public readonly string $action,
        public readonly string $changedData,
        public readonly string $actorId,
        public readonly \DateTimeImmutable $occurredAt,
        public readonly string $title,
    ) {
    }
}
