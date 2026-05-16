<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Infrastructure\Messenger\Message;

final class RecordTaskEventMessage
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $teamId,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}
