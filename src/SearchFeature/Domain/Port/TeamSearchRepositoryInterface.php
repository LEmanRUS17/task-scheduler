<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface TeamSearchRepositoryInterface
{
    /**
     * Returns a page of matching team ids, ordered by relevance, plus the total match count.
     *
     * @param list<string> $statuses
     * @return array{ids: list<string>, total: int}
     */
    public function search(
        string $query,
        string $userId,
        array $statuses,
        bool $ownedOnly,
        int $limit,
        int $offset,
    ): array;
}
