<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Infrastructure\Messenger\Message;

final class RecordTaskActionMessage
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $action,
        public readonly string $actorId,
        public readonly string $metadata,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}
