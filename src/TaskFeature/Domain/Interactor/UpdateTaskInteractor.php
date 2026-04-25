<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;

final class UpdateTaskInteractor
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    public function update(
        string $id,
        ?TaskTitle $title,
        ?TaskPriority $priority,
        ?\DateTimeImmutable $scheduledStart,
        ?\DateTimeImmutable $scheduledEnd,
        ?int $estimatedTime,
    ): Task {
        $task = $this->tasks->findById(TaskId::fromString($id));

        if ($task === null) {
            throw new \DomainException("Task {$id} not found");
        }

        $task->update($title, $priority, $scheduledStart, $scheduledEnd, $estimatedTime);

        $this->tasks->save($task);

        return $task;
    }
}
