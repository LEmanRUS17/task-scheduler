<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Infrastructure\ClickHouse;

use App\AuditLogFeature\Domain\Port\AuditEntryStorageInterface;
use App\Shared\ClickHouse\ClickHouseClient;

final class ClickHouseAuditEntryStorage implements AuditEntryStorageInterface
{
    public function __construct(private readonly ClickHouseClient $client)
    {
    }

    public function record(
        string $id,
        string $entityClass,
        string $entityId,
        string $action,
        string $changedData,
        string $actorId,
        \DateTimeImmutable $occurredAt,
        string $title,
    ): void {
        $this->client->insert('audit_log', [[
            'id'           => $id,
            'entity_class' => $entityClass,
            'entity_id'    => $entityId,
            'action'       => $action,
            'changed_data' => $changedData,
            'actor_id'     => $actorId,
            'occurred_at'  => $occurredAt->format('Y-m-d H:i:s'),
            'title'        => $title,
        ]]);
    }
}
