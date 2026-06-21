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

    /** @return Task[] */
    public function findPaginatedByAssigneeUserId(string $userId, int $limit, int $offset): array;

    public function countByAssigneeUserId(string $userId): int;

    /**
     * @param list<string> $ids
     * @return Task[]
     */
    public function findByIds(array $ids): array;

    /** @return Task[] */
    public function findByTeamId(string $teamId): array;

    public function findById(TaskId $id): ?Task;

    public function delete(TaskId $id): void;
}
