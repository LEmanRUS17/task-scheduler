<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Domain\Port;

interface TaskEventStorageInterface
{
    public function record(
        string $taskId,
        string $teamId,
        string $fromStatus,
        string $toStatus,
        \DateTimeImmutable $occurredAt,
    ): void;
}
