<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Port\ClockInterface;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Port\TaskWorkflowInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;

final class CreateTaskInteractor
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
        private readonly TaskWorkflowInterface $workflow,
    ) {}

    public function create(
        TaskTitle $title,
        TaskPriority $priority,
        string $workflowDefinitionTitle,
        string $createdBy,
        ?\DateTimeImmutable $scheduledStart = null,
        ?\DateTimeImmutable $scheduledEnd = null,
        ?int $estimatedTime = null,
    ): Task {
        $task = Task::create(
            TaskId::generate(),
            $title,
            $priority,
            $workflowDefinitionTitle,
            $createdBy,
            $this->clock->now(),
            $scheduledStart,
            $scheduledEnd,
            $estimatedTime,
        );

        $this->workflow->initialize($task);
        $this->tasks->save($task);
        $this->eventDispatcher->dispatch(...$task->pullDomainEvents());

        return $task;
    }
}
