<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Messenger\Message;

final class TaskCreatedMessage
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $title,
        public readonly string $createdBy,
    ) {}
}
