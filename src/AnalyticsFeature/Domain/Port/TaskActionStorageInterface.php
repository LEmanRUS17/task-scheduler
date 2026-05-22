<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Domain\Port;

interface TaskActionStorageInterface
{
    public function record(
        string $taskId,
        string $action,
        string $actorId,
        string $metadata,
        \DateTimeImmutable $occurredAt,
    ): void;
}
