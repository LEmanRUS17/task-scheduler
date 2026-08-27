<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Infrastructure\ClickHouse;

use App\AnalyticsFeature\Domain\Port\TaskEventStorageInterface;
use App\Shared\ClickHouse\ClickHouseClient;

final class ClickHouseTaskEventStorage implements TaskEventStorageInterface
{
    public function __construct(private readonly ClickHouseClient $client)
    {
    }

    public function record(
        string $taskId,
        string $teamId,
        string $fromStatus,
        string $toStatus,
        \DateTimeImmutable $occurredAt,
    ): void {
        $this->client->insert('task_events', [[
            'task_id'     => $taskId,
            'team_id'     => $teamId,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
        ]]);
    }
}
