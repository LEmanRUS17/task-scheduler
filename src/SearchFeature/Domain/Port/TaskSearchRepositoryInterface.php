<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface TaskSearchRepositoryInterface
{
    /**
     * Returns a page of matching task ids, ordered by relevance, plus the total match count.
     *
     * @return array{ids: list<string>, total: int}
     */
    public function search(
        string $query,
        string $userId,
        ?string $teamId,
        ?string $status,
        int $limit,
        int $offset,
    ): array;
}
