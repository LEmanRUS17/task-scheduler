<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Repository;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

interface WorkflowRepositoryInterface
{
    public function save(Workflow $workflow): void;

    public function findById(WorkflowId $id): ?Workflow;

    /** @return Workflow[] */
    public function findAll(): array;
}
