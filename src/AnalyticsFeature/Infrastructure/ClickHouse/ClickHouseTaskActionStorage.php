<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Infrastructure\ClickHouse;

use App\AnalyticsFeature\Domain\Port\TaskActionStorageInterface;

final class ClickHouseTaskActionStorage implements TaskActionStorageInterface
{
    public function __construct(private readonly ClickHouseClient $client)
    {
    }

    public function record(
        string $taskId,
        string $action,
        string $actorId,
        string $metadata,
        \DateTimeImmutable $occurredAt,
    ): void {
        $this->client->insert('task_actions', [[
            'task_id'     => $taskId,
            'action'      => $action,
            'actor_id'    => $actorId,
            'metadata'    => $metadata,
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
        ]]);
    }
}
