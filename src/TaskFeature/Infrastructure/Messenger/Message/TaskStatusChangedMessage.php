<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Messenger\Message;

final class TaskStatusChangedMessage
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $transitionId,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly string $workflowDefinitionTitle,
        public readonly ?string $teamId,
    ) {}
}
