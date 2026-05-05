<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Messenger\Message;

final class TaskAssigneeAddedMessage
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $userId,
    ) {}
}
