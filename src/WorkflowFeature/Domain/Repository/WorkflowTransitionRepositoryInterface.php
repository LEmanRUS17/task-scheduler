<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Repository;

use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;

interface WorkflowTransitionRepositoryInterface
{
    public function save(WorkflowTransition $transition): void;

    public function findById(WorkflowTransitionId $id): ?WorkflowTransition;

    /** @return WorkflowTransition[] */
    public function findByWorkflowId(WorkflowId $workflowId): array;

    public function existsByName(WorkflowId $workflowId, string $name): bool;
}
