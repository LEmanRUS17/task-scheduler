<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Event;

final class TaskClosed
{
    public function __construct(
        public readonly string $taskId,
        public readonly \DateTimeImmutable $closedAt,
    ) {
    }
}
