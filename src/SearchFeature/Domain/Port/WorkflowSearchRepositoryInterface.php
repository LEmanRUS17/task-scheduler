<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface WorkflowSearchRepositoryInterface
{
    /**
     * Returns a page of matching workflow ids, ordered by relevance, plus the total match count.
     *
     * @return array{ids: list<string>, total: int}
     */
    public function search(string $query, string $userId, bool $ownedOnly, int $limit, int $offset): array;
}
