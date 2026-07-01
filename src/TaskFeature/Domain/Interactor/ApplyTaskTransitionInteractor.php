<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Event\TaskClosed;
use App\TaskFeature\Domain\Event\TaskStatusChanged;
use App\TaskFeature\Domain\Port\ClockInterface;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Port\TaskWorkflowInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;

final class ApplyTaskTransitionInteractor
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskWorkflowInterface $workflow,
        private readonly WorkflowTransitionRepositoryInterface $transitions,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function apply(string $taskId, string $transitionId): Task
    {
        $task = $this->tasks->findById(TaskId::fromString($taskId));

        if ($task === null) {
            throw new \DomainException("Task {$taskId} not found");
        }

        if ($task->isClosed()) {
            throw new \DomainException("Task {$taskId} is closed and must be reopened before it can be transitioned");
        }

        $transition = $this->transitions->findById(WorkflowTransitionId::fromString($transitionId));

        if ($transition === null) {
            throw new \DomainException("Transition {$transitionId} not found");
        }

        $fromStatus = $task->getWorkflowStatus();

        $this->workflow->applyTransition($task, $transition->name()->value());

        $this->applyClosingState($task);

        $this->tasks->save($task);

        $this->eventDispatcher->dispatch(new TaskStatusChanged(
            taskId: $task->id()->value(),
            transitionId: $transition->id()->value(),
            fromStatus: $fromStatus,
            toStatus: $task->getWorkflowStatus(),
            workflowDefinitionTitle: $task->getWorkflowDefinitionTitle(),
            teamId: $task->teamId(),
        ));

        return $task;
    }

    private function applyClosingState(Task $task): void
    {
        try {
            $workflowId = WorkflowId::fromString($task->getWorkflowDefinitionTitle());
        } catch (\InvalidArgumentException) {
            return;
        }

        $status = $this->statuses->findById($workflowId, $task->getWorkflowStatus());

        if ($status !== null && $status->isFinal()) {
            $closedAt = $this->clock->now();
            $task->close($closedAt);
            $this->eventDispatcher->dispatch(new TaskClosed($task->id()->value(), $closedAt));
        }
    }
}
