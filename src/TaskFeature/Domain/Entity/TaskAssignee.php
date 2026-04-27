<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Entity;

use App\TaskFeature\Domain\ValueObject\TaskId;

final class TaskAssignee
{
    private string $taskId;
    private string $userId;
    private \DateTimeImmutable $assignedAt;

    private function __construct(
        TaskId $taskId,
        string $userId,
        \DateTimeImmutable $assignedAt,
    ) {
        $this->taskId = $taskId->value();
        $this->userId = $userId;
        $this->assignedAt = $assignedAt;
    }

    public static function assign(
        TaskId $taskId,
        string $userId,
        \DateTimeImmutable $assignedAt,
    ): self {
        return new self($taskId, $userId, $assignedAt);
    }

    public function taskId(): TaskId
    {
        return TaskId::fromString($this->taskId);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function assignedAt(): \DateTimeImmutable
    {
        return $this->assignedAt;
    }
}
