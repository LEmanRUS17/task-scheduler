<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Domain\Port;

interface AnalyticsQueryInterface
{
    /** @return array<array{status: string, avg_seconds: float}> */
    public function avgTimePerStatus(string $teamId, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    public function completedCount(string $teamId, string $finalStatus, \DateTimeImmutable $from, \DateTimeImmutable $to): int;

    /** @return array<array{day: string, count: int}> */
    public function throughputPerDay(string $teamId, string $finalStatus, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /** @return array<array{action: string, count: int}> */
    public function crudActionsCount(string $teamId, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
