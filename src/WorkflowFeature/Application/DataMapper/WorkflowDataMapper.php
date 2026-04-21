<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DataMapper;

use App\WorkflowFeature\Application\DTOResponse\WorkflowResponseDTO;
use App\WorkflowFeature\Application\DTOResponse\WorkflowStatusResponseDTO;
use App\WorkflowFeature\Application\DTOResponse\WorkflowTransitionResponseDTO;
use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use App\WorkflowFeatureApi\DTORequest\AddStatusRequestInterface;
use App\WorkflowFeatureApi\DTORequest\AddTransitionRequestInterface;
use App\WorkflowFeatureApi\DTORequest\CreateWorkflowRequestInterface;

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

    public function workflowToResponse(Workflow $workflow): WorkflowResponseDTO
    {
        return new WorkflowResponseDTO(
            $workflow->id()->value(),
            $workflow->title()->value(),
            $workflow->createdBy(),
            $workflow->createdAt(),
        );
    }

    public function statusToResponse(WorkflowStatus $status): WorkflowStatusResponseDTO
    {
        return new WorkflowStatusResponseDTO(
            $status->id()->value(),
            $status->workflowId()->value(),
            $status->label()->value(),
            $status->isInitial(),
            $status->createdAt(),
        );
    }

    public function transitionToResponse(WorkflowTransition $transition): WorkflowTransitionResponseDTO
    {
        return new WorkflowTransitionResponseDTO(
            $transition->id()->value(),
            $transition->workflowId()->value(),
            $transition->name()->value(),
            $transition->fromStatusLabel()->value(),
            $transition->toStatusLabel()->value(),
            $transition->createdAt(),
        );
    }
}
