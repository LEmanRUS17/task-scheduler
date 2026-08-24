<?php

declare(strict_types=1);

namespace App\TaskFeatureApi\Service;

/**
 * A narrow, standalone contract (deliberately not part of {@see TaskServiceInterface}) so that
 * consumers outside TaskFeature — notably WorkflowFeature — can depend on task/workflow usage
 * data without pulling in TaskApiService's full dependency graph, which itself depends on
 * WorkflowFeatureApi\Service\WorkflowServiceInterface and would otherwise create a container
 * circular reference.
 */
interface TaskWorkflowUsageServiceInterface
{
    /**
     * Counts the team's tasks per workflow, for the given workflow ids. Workflow ids with no
     * tasks are omitted from the result.
     *
     * @param list<string> $workflowIds
     * @return array<string, int> workflow id => task count
     */
    public function countByWorkflowIds(array $workflowIds, string $teamId): array;
}
