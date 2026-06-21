<?php

declare(strict_types=1);

namespace App\SearchFeatureApi\Contract;

interface SearchServiceInterface
{
    /**
     * Returns a page of matching task ids, ordered by relevance, plus the total match count.
     *
     * @return array{ids: list<string>, total: int}
     */
    public function searchTasks(
        string $query,
        string $userId,
        ?string $teamId = null,
        ?string $status = null,
        int $limit = 10,
        int $offset = 0,
    ): array;

    /**
     * Returns a page of matching team ids, ordered by relevance, plus the total match count.
     *
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
    ): array;

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
