<?php

declare(strict_types=1);

namespace App\SearchFeatureApi\Contract;

use App\SearchFeatureApi\DTOResponse\TaskSearchResultInterface;

interface SearchServiceInterface
{
    /** @return TaskSearchResultInterface[] */
    public function searchTasks(string $query, string $userId, ?string $teamId = null, ?string $status = null): array;
}
