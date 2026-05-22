<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface TaskSearchRepositoryInterface
{
    /**
     * @param string $query
     * @param string|null $teamId
     * @param string|null $status
     *
     * @return array
     */
    public function search(string $query, ?string $teamId, ?string $status): array;
}
