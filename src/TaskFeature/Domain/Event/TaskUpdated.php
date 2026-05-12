<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Event;

use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;

final class TaskUpdated
{
    public function __construct(
        public readonly TaskId $id,
        public readonly TaskTitle $title,
        public readonly TaskPriority $priority,
    ) {}
}
