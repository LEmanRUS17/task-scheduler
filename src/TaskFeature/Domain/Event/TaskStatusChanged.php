<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Event;

final class TaskStatusChanged
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly string $workflowDefinitionTitle,
        public readonly ?string $teamId,
    ) {}
}
