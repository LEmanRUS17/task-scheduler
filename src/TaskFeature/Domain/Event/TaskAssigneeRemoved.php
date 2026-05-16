<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Event;

use App\TaskFeature\Domain\ValueObject\TaskId;

final class TaskAssigneeRemoved
{
    public function __construct(
        public readonly TaskId $taskId,
        public readonly string $userId,
    ) {}
}
