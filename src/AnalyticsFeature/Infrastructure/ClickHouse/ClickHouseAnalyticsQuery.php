<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Infrastructure\ClickHouse;

use App\AnalyticsFeature\Domain\Port\AnalyticsQueryInterface;

final class ClickHouseAnalyticsQuery implements AnalyticsQueryInterface
{
    public function __construct(private readonly ClickHouseClient $client) {}

    public function avgTimePerStatus(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $sql = sprintf(
            "SELECT from_status AS status, avg(dateDiff('second', occurred_at, next_occurred_at)) AS avg_seconds
            FROM (
                SELECT
                    from_status,
                    occurred_at,
                    leadInFrame(occurred_at, 1, toDateTime('1970-01-01 00:00:00'))
                        OVER (PARTITION BY task_id ORDER BY occurred_at
                              ROWS BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING) AS next_occurred_at
                FROM task_events
                WHERE occurred_at >= '%s' AND occurred_at <= '%s'
            )
            WHERE next_occurred_at > occurred_at
            GROUP BY status",
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s'),
        );

        $rows = $this->client->query($sql);

        return array_map(
            static fn(array $row): array => [
                'status' => (string) $row['status'],
                'avg_seconds' => (float) $row['avg_seconds'],
            ],
            $rows,
        );
    }

    public function completedCount(string $finalStatus, \DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        $sql = sprintf(
            "SELECT count() AS cnt
            FROM task_events
            WHERE to_status = '%s' AND occurred_at >= '%s' AND occurred_at <= '%s'",
            $this->escape($finalStatus),
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s'),
        );

        $rows = $this->client->query($sql);

        return (int) ($rows[0]['cnt'] ?? 0);
    }

    public function throughputPerDay(string $finalStatus, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $sql = sprintf(
            "SELECT toString(toDate(occurred_at)) AS day, count() AS count
            FROM task_events
            WHERE to_status = '%s' AND occurred_at >= '%s' AND occurred_at <= '%s'
            GROUP BY day
            ORDER BY day",
            $this->escape($finalStatus),
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s'),
        );

        $rows = $this->client->query($sql);

        return array_map(
            static fn(array $row): array => [
                'day' => (string) $row['day'],
                'count' => (int) $row['count'],
            ],
            $rows,
        );
    }

    public function crudActionsCount(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $sql = sprintf(
            "SELECT a.action AS action, count() AS count
            FROM task_actions AS a
            INNER JOIN (
                SELECT DISTINCT task_id FROM task_events
            ) AS t ON a.task_id = t.task_id
            WHERE a.occurred_at >= '%s' AND a.occurred_at <= '%s'
            GROUP BY action
            ORDER BY action",
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s'),
        );

        $rows = $this->client->query($sql);

        return array_map(
            static fn(array $row): array => [
                'action' => (string) $row['action'],
                'count' => (int) $row['count'],
            ],
            $rows,
        );
    }

    private function escape(string $value): string
    {
        return str_replace("'", "\\'", $value);
    }
}
