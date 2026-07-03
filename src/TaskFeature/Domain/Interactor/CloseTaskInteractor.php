<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Event\TaskClosed;
use App\TaskFeature\Domain\Port\ClockInterface;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;

final class CloseTaskInteractor
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function close(string $taskId): Task
    {
        $task = $this->tasks->findById(TaskId::fromString($taskId));

        if ($task === null) {
            throw new \DomainException("Task {$taskId} not found");
        }

        $closedAt = $this->clock->now();
        $task->close($closedAt);

        $this->tasks->save($task);

        $this->eventDispatcher->dispatch(new TaskClosed($task->id()->value(), $closedAt));

        return $task;
    }
}
