<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

final class NullWorkflowRepository implements WorkflowRepositoryInterface
{
    public function save(Workflow $workflow): void {}

    public function findById(WorkflowId $id): ?Workflow
    {
        return null;
    }

    /** @return Workflow[] */
    public function findAll(): array
    {
        return [];
    }
}
