<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\Service;

use App\WorkflowFeatureApi\DTORequest\AddStatusRequestInterface;
use App\WorkflowFeatureApi\DTORequest\AddTransitionRequestInterface;
use App\WorkflowFeatureApi\DTORequest\CreateWorkflowRequestInterface;
use App\WorkflowFeatureApi\DTORequest\UpdateStatusRequestInterface;
use App\WorkflowFeatureApi\DTORequest\UpdateTransitionRequestInterface;
use App\WorkflowFeatureApi\DTORequest\UpdateWorkflowRequestInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowStatusResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowTransitionResponseInterface;

interface WorkflowServiceInterface
{
    public function create(CreateWorkflowRequestInterface $request, string $createdBy): WorkflowResponseInterface;

    public function update(string $id, UpdateWorkflowRequestInterface $request): WorkflowResponseInterface;

    public function getById(string $id): ?WorkflowResponseInterface;

    /** @return WorkflowResponseInterface[] */
    public function getList(): array;

    /**
     * Returns a single page of workflows ordered by creation date (newest first).
     *
     * @return WorkflowResponseInterface[]
     */
    public function getPage(int $limit, int $offset): array;

    public function countAll(): int;

    /**
     * Returns workflows for the given ids, preserving the order of the ids.
     *
     * @param list<string> $ids
     * @return WorkflowResponseInterface[]
     */
    public function getByIds(array $ids): array;

    public function addStatus(string $workflowId, AddStatusRequestInterface $request): WorkflowStatusResponseInterface;

    public function updateStatus(
        string $workflowId,
        string $statusId,
        UpdateStatusRequestInterface $request,
    ): WorkflowStatusResponseInterface;

    public function getStatusById(string $workflowId, string $statusId): ?WorkflowStatusResponseInterface;

    /** @return WorkflowStatusResponseInterface[] */
    public function getStatuses(string $workflowId): array;

    public function addTransition(
        string $workflowId,
        AddTransitionRequestInterface $request,
    ): WorkflowTransitionResponseInterface;

    public function updateTransition(
        string $workflowId,
        string $transitionId,
        UpdateTransitionRequestInterface $request,
    ): WorkflowTransitionResponseInterface;

    public function getTransitionById(
        string $workflowId,
        string $transitionId,
    ): ?WorkflowTransitionResponseInterface;

    /** @return WorkflowTransitionResponseInterface[] */
    public function getTransitions(string $workflowId): array;
}
