<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Repository;

use App\TaskFeature\Domain\Entity\TaskStatusHistory;

interface TaskStatusHistoryRepositoryInterface
{
    public function save(TaskStatusHistory $entry): void;

    /** @return TaskStatusHistory[] */
    public function findByTaskId(string $taskId): array;
}
