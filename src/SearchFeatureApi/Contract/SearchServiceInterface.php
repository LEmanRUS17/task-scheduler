<?php

declare(strict_types=1);

namespace App\SearchFeatureApi\Contract;

use App\SearchFeatureApi\DTOResponse\TaskSearchResultInterface;
use App\SearchFeatureApi\DTOResponse\TeamSearchResultInterface;

interface SearchServiceInterface
{
    /** @return TaskSearchResultInterface[] */
    public function searchTasks(string $query, string $userId, ?string $teamId = null, ?string $status = null): array;

    /**
     * @param list<string> $statuses
     * @return TeamSearchResultInterface[]
     */
    public function searchTeams(string $query, string $userId, array $statuses = [], bool $ownedOnly = false): array;

    /**
     * Returns a page of matching workflow ids, ordered by relevance, plus the total match count.
     *
     * @return array{ids: list<string>, total: int}
     */
    public function searchWorkflows(
        string $query,
        string $userId,
        bool $ownedOnly = false,
        int $limit = 10,
        int $offset = 0,
    ): array;
}
