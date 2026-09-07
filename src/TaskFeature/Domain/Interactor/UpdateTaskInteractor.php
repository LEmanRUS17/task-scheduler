<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Event\TaskUpdated;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Port\TaskWorkflowInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;

final class UpdateTaskInteractor
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly TaskWorkflowInterface $workflow,
    ) {
    }

    public function update(
        string $id,
        ?TaskTitle $title,
        ?TaskPriority $priority,
        ?\DateTimeImmutable $scheduledStart,
        ?\DateTimeImmutable $scheduledEnd,
        ?int $estimatedTime,
        ?string $teamId = null,
        ?string $workflowDefinitionTitle = null,
    ): Task {
        $task = $this->tasks->findById(TaskId::fromString($id));

        if ($task === null) {
            throw new \DomainException("Task {$id} not found");
        }

        $workflowChanged = $workflowDefinitionTitle !== null
            && $workflowDefinitionTitle !== $task->getWorkflowDefinitionTitle();

        $task->update(
            $title,
            $priority,
            $scheduledStart,
            $scheduledEnd,
            $estimatedTime,
            $teamId,
            $workflowDefinitionTitle,
        );

        if ($workflowChanged) {
            $this->workflow->initialize($task);
        }

        $this->tasks->save($task);

        $this->eventDispatcher->dispatch(new TaskUpdated($id));

        return $task;
    }
}
