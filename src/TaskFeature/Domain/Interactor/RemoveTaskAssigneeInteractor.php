<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Repository\TaskAssigneeRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;

final class RemoveTaskAssigneeInteractor
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskAssigneeRepositoryInterface $assignees,
    ) {}

    public function remove(TaskId $taskId, string $userId): void
    {
        if ($this->tasks->findById($taskId) === null) {
            throw new \DomainException("Task {$taskId->value()} not found");
        }

        $assignee = $this->assignees->findByTaskAndUser($taskId, $userId);

        if ($assignee === null) {
            throw new \DomainException("User {$userId} is not assigned to this task");
        }

        $this->assignees->delete($assignee);
    }
}
