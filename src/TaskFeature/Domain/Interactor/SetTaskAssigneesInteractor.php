<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\TaskAssignee;
use App\TaskFeature\Domain\Event\TaskAssigneeAdded;
use App\TaskFeature\Domain\Event\TaskAssigneeRemoved;
use App\TaskFeature\Domain\Port\ClockInterface;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Repository\TaskAssigneeRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;

final class SetTaskAssigneesInteractor
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskAssigneeRepositoryInterface $assignees,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    /** @param string[] $assigneeIds */
    public function set(TaskId $taskId, array $assigneeIds): void
    {
        if ($this->tasks->findById($taskId) === null) {
            throw new \DomainException("Task {$taskId->value()} not found");
        }

        $desired = array_values(array_unique($assigneeIds));

        $currentByUserId = [];
        foreach ($this->assignees->findByTaskId($taskId) as $assignee) {
            $currentByUserId[$assignee->userId()] = $assignee;
        }

        foreach ($desired as $userId) {
            if (isset($currentByUserId[$userId])) {
                continue;
            }

            $this->assignees->save(TaskAssignee::assign($taskId, $userId, $this->clock->now()));
            $this->eventDispatcher->dispatch(new TaskAssigneeAdded($taskId, $userId));
        }

        foreach ($currentByUserId as $userId => $assignee) {
            if (in_array($userId, $desired, true)) {
                continue;
            }

            $this->assignees->delete($assignee);
            $this->eventDispatcher->dispatch(new TaskAssigneeRemoved($taskId, $userId));
        }
    }
}
