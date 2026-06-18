<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface TeamSearchRepositoryInterface
{
    /**
     * @param list<string> $statuses
     * @return array<int, array{teamId: string, title: string, status: string}>
     */
    public function search(string $query, string $userId, array $statuses, bool $ownedOnly): array;
}
