<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Event\TaskReopened;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;

final class ReopenTaskInteractor
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function reopen(string $taskId): Task
    {
        $task = $this->tasks->findById(TaskId::fromString($taskId));

        if ($task === null) {
            throw new \DomainException("Task {$taskId} not found");
        }

        $task->reopen();

        $this->tasks->save($task);

        $this->eventDispatcher->dispatch(new TaskReopened($task->id()->value()));

        return $task;
    }
}
