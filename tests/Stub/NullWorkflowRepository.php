<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

final class NullWorkflowRepository implements WorkflowRepositoryInterface
{
    public function save(Workflow $workflow): void
    {
    }

    public function findById(WorkflowId $id): ?Workflow
    {
        return null;
    }

    public function findDefaultByCreatedBy(string $createdBy): ?Workflow
    {
        return null;
    }

    public function findByIds(array $ids): array
    {
        return [];
    }

    /** @return Workflow[] */
    public function findAll(): array
    {
        return [];
    }

    public function findByCreatedBy(string $createdBy, int $limit, int $offset): array
    {
        return [];
    }

    public function countByCreatedBy(string $createdBy): int
    {
        return 0;
    }
}
