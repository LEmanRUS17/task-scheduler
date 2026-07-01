<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface TagSearchRepositoryInterface
{
    /**
     * Returns a page of matching tag ids owned by the given user, ordered by relevance,
     * plus the total match count.
     *
     * @return array{ids: list<string>, total: int}
     */
    public function search(string $query, string $ownerId, int $limit, int $offset): array;
}
