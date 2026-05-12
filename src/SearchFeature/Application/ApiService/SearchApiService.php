<?php

declare(strict_types=1);

namespace App\SearchFeature\Application\ApiService;

use App\SearchFeature\Application\DTOResponse\TaskSearchResult;
use App\SearchFeature\Domain\Port\TaskSearchRepositoryInterface;
use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\SearchFeatureApi\DTOResponse\TaskSearchResultInterface;

final class SearchApiService implements SearchServiceInterface
{
    public function __construct(private readonly TaskSearchRepositoryInterface $repository) {}

    /** @return TaskSearchResultInterface[] */
    public function searchTasks(string $query, ?string $teamId = null, ?string $status = null): array
    {
        return array_map(
            static fn(array $row) => new TaskSearchResult($row['taskId'], $row['title'], $row['status']),
            $this->repository->search($query, $teamId, $status),
        );
    }
}
