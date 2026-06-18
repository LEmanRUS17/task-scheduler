<?php

declare(strict_types=1);

namespace App\SearchFeature\Application\ApiService;

use App\SearchFeature\Application\DTOResponse\TaskSearchResult;
use App\SearchFeature\Application\DTOResponse\TeamSearchResult;
use App\SearchFeature\Domain\Port\TaskSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\TeamSearchRepositoryInterface;
use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\SearchFeatureApi\DTOResponse\TaskSearchResultInterface;
use App\SearchFeatureApi\DTOResponse\TeamSearchResultInterface;

final class SearchApiService implements SearchServiceInterface
{
    public function __construct(
        private readonly TaskSearchRepositoryInterface $repository,
        private readonly TeamSearchRepositoryInterface $teamRepository,
    ) {
    }

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
     * @return TeamSearchResultInterface[]
     */
    public function searchTeams(string $query, string $userId, array $statuses = [], bool $ownedOnly = false): array
    {
        return array_map(
            static fn(array $row) => new TeamSearchResult($row['teamId'], $row['title'], $row['status']),
            $this->teamRepository->search($query, $userId, $statuses, $ownedOnly),
        );
    }
}
