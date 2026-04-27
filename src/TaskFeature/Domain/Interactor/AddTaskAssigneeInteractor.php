<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\TaskAssignee;
use App\TaskFeature\Domain\Port\ClockInterface;
use App\TaskFeature\Domain\Port\TeamMembershipInterface;
use App\TaskFeature\Domain\Repository\TaskAssigneeRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;

final class AddTaskAssigneeInteractor
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskAssigneeRepositoryInterface $assignees,
        private readonly TeamMembershipInterface $teamMembership,
        private readonly ClockInterface $clock,
    ) {}

    public function add(TaskId $taskId, string $userId): TaskAssignee
    {
        $task = $this->tasks->findById($taskId);

        if ($task === null) {
            throw new \DomainException("Task {$taskId->value()} not found");
        }

        if ($task->teamId() === null) {
            throw new \DomainException('Cannot assign a user to a task without a team');
        }

        if (!$this->teamMembership->isMember($task->teamId(), $userId)) {
            throw new \DomainException("User {$userId} is not a member of the task team");
        }

        if ($this->assignees->findByTaskAndUser($taskId, $userId) !== null) {
            throw new \DomainException("User {$userId} is already assigned to this task");
        }

        $assignee = TaskAssignee::assign($taskId, $userId, $this->clock->now());
        $this->assignees->save($assignee);

        return $assignee;
    }
}
