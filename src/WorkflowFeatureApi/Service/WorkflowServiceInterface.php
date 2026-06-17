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

    public function addStatus(string $workflowId, AddStatusRequestInterface $request): WorkflowStatusResponseInterface;

    public function updateStatus(
        string $workflowId,
        string $statusId,
        UpdateStatusRequestInterface $request,
    ): WorkflowStatusResponseInterface;

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

    /** @return WorkflowTransitionResponseInterface[] */
    public function getTransitions(string $workflowId): array;
}
