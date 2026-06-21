<?php

declare(strict_types=1);

namespace App\SearchFeature\Application\ApiService;

use App\SearchFeature\Application\DTOResponse\TeamSearchResult;
use App\SearchFeature\Domain\Port\TaskSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\TeamSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\WorkflowSearchRepositoryInterface;
use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\SearchFeatureApi\DTOResponse\TeamSearchResultInterface;

final class SearchApiService implements SearchServiceInterface
{
    public function __construct(
        private readonly TaskSearchRepositoryInterface $repository,
        private readonly TeamSearchRepositoryInterface $teamRepository,
        private readonly WorkflowSearchRepositoryInterface $workflowRepository,
    ) {
    }

    /** @return array{ids: list<string>, total: int} */
    public function searchTasks(
        string $query,
        string $userId,
        ?string $teamId = null,
        ?string $status = null,
        int $limit = 10,
        int $offset = 0,
    ): array {
        return $this->repository->search($query, $userId, $teamId, $status, $limit, $offset);
    }

    /**
     * @param list<string> $statuses
     * @return TeamSearchResultInterface[]
     */
    public function searchTeams(string $query, string $userId, array $statuses = [], bool $ownedOnly = false): array
    {
        return array_map(
            static fn(array $row) => new TeamSearchResult($row['teamId'], $row['title'], $row['status']),
            $this->teamRepository->search($query, $userId, $statuses, $ownedOnly),
        );
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
