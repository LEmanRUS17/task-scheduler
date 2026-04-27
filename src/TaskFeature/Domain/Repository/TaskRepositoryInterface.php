<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Repository;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\ValueObject\TaskId;

interface TaskRepositoryInterface
{
    public function save(Task $task): void;

    /** @return Task[] */
    public function findAll(): array;

    /** @return Task[] */
    public function findByAssigneeUserId(string $userId): array;

    public function findById(TaskId $id): ?Task;

    public function delete(TaskId $id): void;
}
