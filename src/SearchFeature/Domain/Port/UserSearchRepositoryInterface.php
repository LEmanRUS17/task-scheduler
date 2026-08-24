<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface UserSearchRepositoryInterface
{
    /**
     * Returns a page of matching user ids that are members of the given team, ordered by
     * relevance, plus the total match count. Matches against nickname and full name (ФИО).
     *
     * @return array{ids: list<string>, total: int}
     */
    public function searchInTeam(string $teamId, string $query, int $limit, int $offset): array;
}
