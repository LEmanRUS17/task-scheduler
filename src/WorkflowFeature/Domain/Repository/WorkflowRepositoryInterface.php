<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Repository;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

interface WorkflowRepositoryInterface
{
    public function save(Workflow $workflow): void;

    public function findById(WorkflowId $id): ?Workflow;

    /**
     * @param list<string> $ids
     * @return Workflow[]
     */
    public function findByIds(array $ids): array;

    /** @return Workflow[] */
    public function findAll(): array;

    /** @return Workflow[] */
    public function findPaginated(int $limit, int $offset): array;

    public function count(): int;
}
