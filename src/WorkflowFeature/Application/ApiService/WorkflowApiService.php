<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\ApiService;

use App\WorkflowFeature\Application\DataMapper\WorkflowDataMapper;
use App\WorkflowFeature\Application\DTORequestValidator\WorkflowValidatorInterface;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowStatusInteractor;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowTransitionInteractor;
use App\WorkflowFeature\Domain\Interactor\CreateWorkflowInteractor;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowInteractor;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowStatusInteractor;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowTransitionInteractor;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use App\WorkflowFeatureApi\DTORequest\AddStatusRequestInterface;
use App\WorkflowFeatureApi\DTORequest\AddTransitionRequestInterface;
use App\WorkflowFeatureApi\DTORequest\UpdateStatusRequestInterface;
use App\WorkflowFeatureApi\DTORequest\UpdateTransitionRequestInterface;
use App\WorkflowFeatureApi\DTORequest\UpdateWorkflowRequestInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeatureApi\DTORequest\CreateWorkflowRequestInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowStatusResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowTransitionResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;

final class WorkflowApiService implements WorkflowServiceInterface
{
    public function __construct(
        private readonly CreateWorkflowInteractor $createInteractor,
        private readonly UpdateWorkflowInteractor $updateInteractor,
        private readonly AddWorkflowStatusInteractor $addStatusInteractor,
        private readonly UpdateWorkflowStatusInteractor $updateStatusInteractor,
        private readonly AddWorkflowTransitionInteractor $addTransitionInteractor,
        private readonly UpdateWorkflowTransitionInteractor $updateTransitionInteractor,
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly WorkflowTransitionRepositoryInterface $transitions,
        private readonly WorkflowDataMapper $dataMapper,
        private readonly WorkflowValidatorInterface $validator,
        private readonly DescriptionServiceInterface $descriptions,
        private readonly TagServiceInterface $tagService,
    ) {
    }

    public function create(CreateWorkflowRequestInterface $request, string $createdBy): WorkflowResponseInterface
    {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $tagIds = array_values(array_unique($request->getTagIds()));
        $missingTagIds = array_values(array_diff($tagIds, $this->tagService->filterExistingTagIds($tagIds)));
        if ($missingTagIds !== []) {
            throw new \InvalidArgumentException(json_encode([
                'tagIds' => sprintf('Unknown tag ids: %s', implode(', ', $missingTagIds)),
            ]) ?: '{}');
        }

        $title = $this->dataMapper->requestToTitle($request);
        $workflow = $this->createInteractor->create(
            $title,
            $createdBy,
            array_map($this->dataMapper->requestToNewStatus(...), $request->getStatuses()),
            array_map($this->dataMapper->requestToNewTransition(...), $request->getTransitions()),
        );

        $description = $request->getDescription();
        if ($description !== null) {
            $this->descriptions->set(Workflow::class, $workflow->id()->value(), $description);
        }

        foreach ($tagIds as $tagId) {
            $this->tagService->assign($tagId, TagServiceInterface::TYPE_WORKFLOW, $workflow->id()->value(), $createdBy);
        }

        return $this->dataMapper->workflowToResponse($workflow, $description);
    }

    public function update(string $id, UpdateWorkflowRequestInterface $request): WorkflowResponseInterface
    {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $workflow = $this->updateInteractor->update(
            WorkflowId::fromString($id),
            WorkflowTitle::fromString($request->getTitle()),
        );

        $description = $request->getDescription();
        if ($description !== null) {
            $this->descriptions->set(Workflow::class, $workflow->id()->value(), $description);
        }

        return $this->dataMapper->workflowToResponse(
            $workflow,
            $this->descriptions->get(Workflow::class, $workflow->id()->value()),
        );
    }

    public function getById(string $id): ?WorkflowResponseInterface
    {
        $workflow = $this->workflows->findById(WorkflowId::fromString($id));

        return $workflow !== null
            ? $this->dataMapper->workflowToResponse(
                $workflow,
                $this->descriptions->get(Workflow::class, $id),
            )
            : null;
    }

    public function getList(): array
    {
        return array_map(
            fn($workflow) => $this->dataMapper->workflowToResponse(
                $workflow,
                $this->descriptions->get(Workflow::class, $workflow->id()->value()),
            ),
            $this->workflows->findAll(),
        );
    }

    public function getPage(int $limit, int $offset): array
    {
        return array_map(
            fn($workflow) => $this->dataMapper->workflowToResponse($workflow),
            $this->workflows->findPaginated($limit, $offset),
        );
    }

    public function countAll(): int
    {
        return $this->workflows->count();
    }

    public function getByIds(array $ids): array
    {
        $byId = [];
        foreach ($this->workflows->findByIds($ids) as $workflow) {
            $byId[$workflow->id()->value()] = $workflow;
        }

        $result = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $result[] = $this->dataMapper->workflowToResponse($byId[$id]);
            }
        }

        return $result;
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
            $request->isFinal(),
        );

        $description = $request->getDescription();
        if ($description !== null) {
            $this->descriptions->set(WorkflowStatus::class, $status->id()->value(), $description);
        }

        return $this->dataMapper->statusToResponse($status, $description);
    }

    public function updateStatus(
        string $workflowId,
        string $statusId,
        UpdateStatusRequestInterface $request,
    ): WorkflowStatusResponseInterface {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $status = $this->updateStatusInteractor->update(
            WorkflowId::fromString($workflowId),
            WorkflowStatusId::fromString($statusId),
            StatusLabel::fromString($request->getLabel()),
            $request->isFinal(),
        );

        $description = $request->getDescription();
        if ($description !== null) {
            $this->descriptions->set(WorkflowStatus::class, $status->id()->value(), $description);
        }

        return $this->dataMapper->statusToResponse(
            $status,
            $this->descriptions->get(WorkflowStatus::class, $status->id()->value()),
        );
    }

    public function getStatusById(string $workflowId, string $statusId): ?WorkflowStatusResponseInterface
    {
        $status = $this->statuses->findById(WorkflowId::fromString($workflowId), $statusId);

        return $status !== null
            ? $this->dataMapper->statusToResponse(
                $status,
                $this->descriptions->get(WorkflowStatus::class, $status->id()->value()),
            )
            : null;
    }

    public function getStatuses(string $workflowId): array
    {
        return array_map(
            fn($status) => $this->dataMapper->statusToResponse(
                $status,
                $this->descriptions->get(WorkflowStatus::class, $status->id()->value()),
            ),
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
            WorkflowStatusId::fromString($request->getFromStatusId()),
            WorkflowStatusId::fromString($request->getToStatusId()),
        );

        $description = $request->getDescription();
        if ($description !== null) {
            $this->descriptions->set(WorkflowTransition::class, $transition->id()->value(), $description);
        }

        return $this->dataMapper->transitionToResponse($transition, $description);
    }

    public function updateTransition(
        string $workflowId,
        string $transitionId,
        UpdateTransitionRequestInterface $request,
    ): WorkflowTransitionResponseInterface {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $transition = $this->updateTransitionInteractor->update(
            WorkflowId::fromString($workflowId),
            WorkflowTransitionId::fromString($transitionId),
            TransitionName::fromString($request->getName()),
            WorkflowStatusId::fromString($request->getFromStatusId()),
            WorkflowStatusId::fromString($request->getToStatusId()),
        );

        $description = $request->getDescription();
        if ($description !== null) {
            $this->descriptions->set(WorkflowTransition::class, $transition->id()->value(), $description);
        }

        return $this->dataMapper->transitionToResponse(
            $transition,
            $this->descriptions->get(WorkflowTransition::class, $transition->id()->value()),
        );
    }

    public function getTransitionById(
        string $workflowId,
        string $transitionId,
    ): ?WorkflowTransitionResponseInterface {
        $workflow = WorkflowId::fromString($workflowId);
        $transition = $this->transitions->findById(WorkflowTransitionId::fromString($transitionId));

        if ($transition === null || $transition->workflowId()->value() !== $workflow->value()) {
            return null;
        }

        return $this->dataMapper->transitionToResponse(
            $transition,
            $this->descriptions->get(WorkflowTransition::class, $transition->id()->value()),
        );
    }

    public function getTransitions(string $workflowId): array
    {
        return array_map(
            fn($transition) => $this->dataMapper->transitionToResponse(
                $transition,
                $this->descriptions->get(WorkflowTransition::class, $transition->id()->value()),
            ),
            $this->transitions->findByWorkflowId(WorkflowId::fromString($workflowId)),
        );
    }
}
