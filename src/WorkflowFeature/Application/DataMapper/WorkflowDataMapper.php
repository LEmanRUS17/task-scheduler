<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DataMapper;

use App\WorkflowFeature\Application\DTOResponse\WorkflowResponseDTO;
use App\WorkflowFeature\Application\DTOResponse\WorkflowStatusResponseDTO;
use App\WorkflowFeature\Application\DTOResponse\WorkflowTransitionResponseDTO;
use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Interactor\NewWorkflowStatus;
use App\WorkflowFeature\Domain\Interactor\NewWorkflowTransition;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use App\WorkflowFeatureApi\DTORequest\AddStatusRequestInterface;
use App\WorkflowFeatureApi\DTORequest\AddTransitionRequestInterface;
use App\WorkflowFeatureApi\DTORequest\CreateWorkflowRequestInterface;
use App\WorkflowFeatureApi\DTORequest\CreateWorkflowStatusRequestInterface;
use App\WorkflowFeatureApi\DTORequest\CreateWorkflowTransitionRequestInterface;

final class WorkflowDataMapper
{
    public function requestToTitle(CreateWorkflowRequestInterface $request): WorkflowTitle
    {
        return WorkflowTitle::fromString($request->getTitle());
    }

    public function requestToStatusLabel(AddStatusRequestInterface $request): StatusLabel
    {
        return StatusLabel::fromString($request->getLabel());
    }

    public function requestToTransitionName(AddTransitionRequestInterface $request): TransitionName
    {
        return TransitionName::fromString($request->getName());
    }

    public function requestToNewStatus(CreateWorkflowStatusRequestInterface $request): NewWorkflowStatus
    {
        return new NewWorkflowStatus(
            StatusLabel::fromString($request->getLabel()),
            $request->isInitial(),
            $request->isFinal(),
        );
    }

    public function requestToNewTransition(CreateWorkflowTransitionRequestInterface $request): NewWorkflowTransition
    {
        return new NewWorkflowTransition(
            TransitionName::fromString($request->getName()),
            StatusLabel::fromString($request->getFrom()),
            StatusLabel::fromString($request->getTo()),
        );
    }

    public function workflowToResponse(Workflow $workflow, ?string $description = null): WorkflowResponseDTO
    {
        return new WorkflowResponseDTO(
            $workflow->id()->value(),
            $workflow->title()->value(),
            $workflow->createdBy(),
            $workflow->createdAt(),
            $workflow->isDefault(),
            $description,
        );
    }

    public function statusToResponse(WorkflowStatus $status, ?string $description = null): WorkflowStatusResponseDTO
    {
        return new WorkflowStatusResponseDTO(
            $status->id()->value(),
            $status->workflowId()->value(),
            $status->label()->value(),
            $status->isInitial(),
            $status->isFinal(),
            $status->createdAt(),
            $description,
        );
    }

    public function transitionToResponse(
        WorkflowTransition $transition,
        ?string $description = null,
    ): WorkflowTransitionResponseDTO {
        return new WorkflowTransitionResponseDTO(
            $transition->id()->value(),
            $transition->workflowId()->value(),
            $transition->name()->value(),
            $transition->fromStatusId()->value(),
            $transition->toStatusId()->value(),
            $transition->createdAt(),
            $description,
        );
    }
}
