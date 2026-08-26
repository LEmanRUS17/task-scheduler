<?php

declare(strict_types=1);

namespace App\AuditLogFeatureApi\Contract;

use App\AuditLogFeatureApi\DTOResponse\AuditActivityDayResponseInterface;
use App\AuditLogFeatureApi\DTOResponse\AuditEntryResponseInterface;

interface AuditLogServiceInterface
{
    /**
     * @param string[] $entityTypes restrict to these entity types (see AuditEntityTypeCatalog);
     *                               empty means no restriction
     * @return array{entries: AuditEntryResponseInterface[], count: int}
     */
    public function getMyActivity(
        string $userId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        int $limit,
        int $offset,
        array $entityTypes = [],
    ): array;

    /**
     * Counts only named business events (see AuditActivityEventCatalog), e.g. "task_created",
     * "task_closed", "team_created", "comment_created" — raw entity changes that don't match a
     * known event type are excluded.
     *
     * @param string[] $eventTypes restrict to these event types; empty means all known event types
     * @return AuditActivityDayResponseInterface[]
     */
    public function getMyActivityCalendar(
        string $userId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        array $eventTypes = [],
    ): array;
}
