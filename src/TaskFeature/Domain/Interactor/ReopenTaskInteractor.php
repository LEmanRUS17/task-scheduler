<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Event\TaskReopened;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

final class ReopenTaskInteractor
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function reopen(string $taskId): Task
    {
        $task = $this->tasks->findById(TaskId::fromString($taskId));

        if ($task === null) {
            throw new \DomainException("Task {$taskId} not found");
        }

        if ($this->isInFinalStatus($task)) {
            throw new \DomainException("Task {$taskId} is in a final status and cannot be reopened");
        }

        $task->reopen();

        $this->tasks->save($task);

        $this->eventDispatcher->dispatch(new TaskReopened($task->id()->value()));

        return $task;
    }

    private function isInFinalStatus(Task $task): bool
    {
        try {
            $workflowId = WorkflowId::fromString($task->getWorkflowDefinitionTitle());
        } catch (\InvalidArgumentException) {
            return false;
        }

        $status = $this->statuses->findById($workflowId, $task->getWorkflowStatus());

        return $status !== null && $status->isFinal();
    }
}
