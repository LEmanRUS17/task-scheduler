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
     * Excludes personal default workflows (auto-created per user on registration) — used for the
     * public workflow catalog, which should only surface workflows users created themselves.
     *
     * @return Workflow[]
     */
    public function findPaginated(int $limit, int $offset): array;

    /** Counts the same set as {@see findPaginated()}. */
    public function count(): int;
}
