<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Repository;

use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

interface WorkflowStatusRepositoryInterface
{
    public function save(WorkflowStatus $status): void;

    /** @return WorkflowStatus[] */
    public function findByWorkflowId(WorkflowId $workflowId): array;

    public function findByLabel(WorkflowId $workflowId, string $label): ?WorkflowStatus;

    public function hasInitial(WorkflowId $workflowId): bool;
}
