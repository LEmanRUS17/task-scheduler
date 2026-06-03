<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface TaskSearchRepositoryInterface
{
    /**
     * @return array<int, array{taskId: string, title: string, status: string}>
     */
    public function search(string $query, string $userId, ?string $teamId, ?string $status): array;
}
