<?php

declare(strict_types=1);

namespace App\SearchFeature\Application\ApiService;

use App\SearchFeature\Application\DTOResponse\TaskSearchResult;
use App\SearchFeature\Domain\Port\TaskSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\TeamSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\WorkflowSearchRepositoryInterface;
use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\SearchFeatureApi\DTOResponse\TaskSearchResultInterface;

final class SearchApiService implements SearchServiceInterface
{
    public function __construct(
        private readonly TaskSearchRepositoryInterface $repository,
        private readonly TeamSearchRepositoryInterface $teamRepository,
        private readonly WorkflowSearchRepositoryInterface $workflowRepository,
    ) {}

    /** @return TaskSearchResultInterface[] */
    public function searchTasks(string $query, string $userId, ?string $teamId = null, ?string $status = null): array
    {
        return array_map(
            static fn(array $row) => new TaskSearchResult($row['taskId'], $row['title'], $row['status']),
            $this->repository->search($query, $userId, $teamId, $status),
        );
    }

    /**
     * @param list<string> $statuses
     * @return array{ids: list<string>, total: int}
     */
    public function searchTeams(
        string $query,
        string $userId,
        array $statuses = [],
        bool $ownedOnly = false,
        int $limit = 10,
        int $offset = 0,
    ): array {
        return $this->teamRepository->search($query, $userId, $statuses, $ownedOnly, $limit, $offset);
    }

    /** @return array{ids: list<string>, total: int} */
    public function searchWorkflows(
        string $query,
        string $userId,
        bool $ownedOnly = false,
        int $limit = 10,
        int $offset = 0,
    ): array {
        return $this->workflowRepository->search($query, $userId, $ownedOnly, $limit, $offset);
    }
}
