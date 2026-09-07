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

    public function findById(WorkflowId $workflowId, string $statusId): ?WorkflowStatus;

    /**
     * Looks statuses up by id alone, across any workflow — ids are globally unique.
     *
     * @param string[] $ids
     * @return WorkflowStatus[]
     */
    public function findByIds(array $ids): array;

    public function findInitial(WorkflowId $workflowId): ?WorkflowStatus;

    public function hasInitial(WorkflowId $workflowId): bool;
}
