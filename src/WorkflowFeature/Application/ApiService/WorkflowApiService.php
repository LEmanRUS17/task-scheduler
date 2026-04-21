<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\ApiService;

use App\WorkflowFeature\Application\DataMapper\WorkflowDataMapper;
use App\WorkflowFeature\Application\DTORequestValidator\WorkflowValidatorInterface;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowStatusInteractor;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowTransitionInteractor;
use App\WorkflowFeature\Domain\Interactor\CreateWorkflowInteractor;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeatureApi\DTORequest\AddStatusRequestInterface;
use App\WorkflowFeatureApi\DTORequest\AddTransitionRequestInterface;
use App\WorkflowFeatureApi\DTORequest\CreateWorkflowRequestInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowStatusResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowTransitionResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;

final class WorkflowApiService implements WorkflowServiceInterface
{
    public function __construct(
        private readonly CreateWorkflowInteractor $createInteractor,
        private readonly AddWorkflowStatusInteractor $addStatusInteractor,
        private readonly AddWorkflowTransitionInteractor $addTransitionInteractor,
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly WorkflowTransitionRepositoryInterface $transitions,
        private readonly WorkflowDataMapper $dataMapper,
        private readonly WorkflowValidatorInterface $validator,
    ) {
    }

    public function create(CreateWorkflowRequestInterface $request, string $createdBy): WorkflowResponseInterface
    {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $title = $this->dataMapper->requestToTitle($request);
        $workflow = $this->createInteractor->create($title, $createdBy);

        return $this->dataMapper->workflowToResponse($workflow);
    }

    public function getById(string $id): ?WorkflowResponseInterface
    {
        $workflow = $this->workflows->findById(WorkflowId::fromString($id));

        return $workflow !== null ? $this->dataMapper->workflowToResponse($workflow) : null;
    }

    public function getList(): array
    {
        return array_map(
            fn($workflow) => $this->dataMapper->workflowToResponse($workflow),
            $this->workflows->findAll(),
        );
    }

    public function addStatus(string $workflowId, AddStatusRequestInterface $request): WorkflowStatusResponseInterface
    {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $label = StatusLabel::fromString($request->getLabel());
        $status = $this->addStatusInteractor->add(
            WorkflowId::fromString($workflowId),
            $label,
            $request->isInitial(),
        );

        return $this->dataMapper->statusToResponse($status);
    }

    public function getStatuses(string $workflowId): array
    {
        return array_map(
            fn($status) => $this->dataMapper->statusToResponse($status),
            $this->statuses->findByWorkflowId(WorkflowId::fromString($workflowId)),
        );
    }

    public function addTransition(
        string $workflowId,
        AddTransitionRequestInterface $request,
    ): WorkflowTransitionResponseInterface {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $transition = $this->addTransitionInteractor->add(
            WorkflowId::fromString($workflowId),
            TransitionName::fromString($request->getName()),
            StatusLabel::fromString($request->getFromStatusLabel()),
            StatusLabel::fromString($request->getToStatusLabel()),
        );

        return $this->dataMapper->transitionToResponse($transition);
    }

    public function getTransitions(string $workflowId): array
    {
        return array_map(
            fn($transition) => $this->dataMapper->transitionToResponse($transition),
            $this->transitions->findByWorkflowId(WorkflowId::fromString($workflowId)),
        );
    }
}
