<?php

declare(strict_types=1);

namespace App\TaskFeatureApi\Service;

use App\TaskFeatureApi\DTORequest\TaskCreateRequestInterface;
use App\TaskFeatureApi\DTORequest\TaskUpdateRequestInterface;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface as ResponseDTO;
use App\TaskFeatureApi\DTOResponse\TaskStatusHistoryResponseInterface;

interface TaskServiceInterface
{
    /** @return ResponseDTO[] */
    public function getAll(): array;

    /** @return ResponseDTO[] */
    public function getList(string $userId): array;

    /**
     * Returns a single page of the user's tasks ordered by creation date (newest first).
     *
     * @return ResponseDTO[]
     */
    public function getPage(string $userId, int $limit, int $offset): array;

    public function countAll(string $userId): int;

    /**
     * Returns tasks for the given ids, preserving the order of the ids.
     *
     * @param list<string> $ids
     * @return ResponseDTO[]
     */
    public function getByIds(array $ids): array;

    /** @return ResponseDTO[] */
    public function getListByTeam(string $teamId, string $userId): array;

    public function getById(string $id): ?ResponseDTO;

    public function create(
        TaskCreateRequestInterface $dtoRequest,
        string $creatorUserId
    ): ResponseDTO;

    public function update(string $id, TaskUpdateRequestInterface $dtoRequest): ResponseDTO;

    public function applyTransition(string $id, string $transition): ResponseDTO;

    public function addAssignee(string $taskId, string $userId): void;

    public function removeAssignee(string $taskId, string $userId): void;

    public function deleteById(string $id): void;

    /** @return TaskStatusHistoryResponseInterface[] */
    public function getStatusHistory(string $taskId): array;
}
