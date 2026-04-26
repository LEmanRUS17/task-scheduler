<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Port\TaskWorkflowInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;

final class ApplyTaskTransitionInteractor
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskWorkflowInterface $workflow,
        private readonly WorkflowTransitionRepositoryInterface $transitions,
    ) {}

    public function apply(string $taskId, string $transitionId): Task
    {
        $task = $this->tasks->findById(TaskId::fromString($taskId));

        if ($task === null) {
            throw new \DomainException("Task {$taskId} not found");
        }

        $transition = $this->transitions->findById(WorkflowTransitionId::fromString($transitionId));

        if ($transition === null) {
            throw new \DomainException("Transition {$transitionId} not found");
        }

        $this->workflow->applyTransition($task, $transition->name()->value());

        $this->tasks->save($task);

        return $task;
    }
}
