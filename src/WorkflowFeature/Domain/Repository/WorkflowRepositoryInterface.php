<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Repository;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

interface WorkflowRepositoryInterface
{
    public function save(Workflow $workflow): void;

    public function findById(WorkflowId $id): ?Workflow;

    public function findDefaultByCreatedBy(string $createdBy): ?Workflow;

    /**
     * @param list<string> $ids
     * @return Workflow[]
     */
    public function findByIds(array $ids): array;

    /** @return Workflow[] */
    public function findAll(): array;

    /**
     * Returns a page of the given user's own non-default workflows, newest first.
     *
     * @return Workflow[]
     */
    public function findByCreatedBy(string $createdBy, int $limit, int $offset): array;

    /** Counts the same set as {@see findByCreatedBy()}. */
    public function countByCreatedBy(string $createdBy): int;
}
