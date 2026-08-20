<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\ApiService;

use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeatureApi\Service\TaskWorkflowUsageServiceInterface;

final class TaskWorkflowUsageService implements TaskWorkflowUsageServiceInterface
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
    ) {
    }

    public function countByWorkflowIds(array $workflowIds, string $teamId): array
    {
        return $this->tasks->countByWorkflowIdsAndTeamId($workflowIds, $teamId);
    }
}
