<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Event;

final class TaskUpdated
{
    public function __construct(
        public readonly string $taskId,
    ) {
    }
}
