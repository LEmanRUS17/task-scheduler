<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Repository;

use App\TaskFeature\Domain\Entity\TaskAssignee;
use App\TaskFeature\Domain\ValueObject\TaskId;

interface TaskAssigneeRepositoryInterface
{
    public function save(TaskAssignee $assignee): void;

    /** @return TaskAssignee[] */
    public function findByTaskId(TaskId $taskId): array;

    public function deleteByTaskId(TaskId $taskId): void;
}
