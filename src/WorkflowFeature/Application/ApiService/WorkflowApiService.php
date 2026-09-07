<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\ApiService;

use App\WorkflowFeature\Application\DataMapper\WorkflowDataMapper;
use App\WorkflowFeature\Application\DTORequestValidator\WorkflowValidatorInterface;
use App\WorkflowFeature\Domain\Entity\WorkflowTeam;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowStatusInteractor;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowTransitionInteractor;
use App\WorkflowFeature\Domain\Interactor\AttachWorkflowToTeamInteractor;
use App\WorkflowFeature\Domain\Interactor\CreateWorkflowInteractor;
use App\WorkflowFeature\Domain\Interactor\DetachWorkflowFromTeamInteractor;
use App\WorkflowFeature\Domain\Interactor\NewWorkflowStatus;
use App\WorkflowFeature\Domain\Interactor\NewWorkflowTransition;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowInteractor;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowStatusInteractor;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowTransitionInteractor;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTeamRepositoryInterface;
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
use App\TaskFeatureApi\Service\TaskWorkflowUsageServiceInterface;
use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeatureApi\DTORequest\CreateWorkflowRequestInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowListMiniResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowStatusResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowTransitionResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;

final class WorkflowApiService implements WorkflowServiceInterface
{
    public function __construct(
        private readonly CreateWorkflowInteractor $createInteractor,
        private readonly UpdateWorkflowInteractor $updateInteractor,
        private readonly AddWorkflowStatusInteractor $addStatusInteractor,
        private readonly UpdateWorkflowStatusInteractor $updateStatusInteractor,
        private readonly AddWorkflowTransitionInteractor $addTransitionInteractor,
        private readonly UpdateWorkflowTransitionInteractor $updateTransitionInteractor,
        private readonly AttachWorkflowToTeamInteractor $attachToTeamInteractor,
        private readonly DetachWorkflowFromTeamInteractor $detachFromTeamInteractor,
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly WorkflowTransitionRepositoryInterface $transitions,
        private readonly WorkflowTeamRepositoryInterface $workflowTeams,
        private readonly WorkflowDataMapper $dataMapper,
        private readonly WorkflowValidatorInterface $validator,
        private readonly DescriptionServiceInterface $descriptions,
        private readonly TagServiceInterface $tagService,
        private readonly TeamServiceInterface $teamService,
        private readonly TaskWorkflowUsageServiceInterface $taskWorkflowUsage,
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

    public function createDefaultForUser(string $userId): WorkflowResponseInterface
    {
        $existing = $this->workflows->findDefaultByCreatedBy($userId);
        if ($existing !== null) {
            return $this->dataMapper->workflowToResponse($existing);
        }

        $workflow = $this->createInteractor->create(
            WorkflowTitle::fromString('Базовый'),
            $userId,
            [
                new NewWorkflowStatus(StatusLabel::fromString('открыт'), true, false),
                new NewWorkflowStatus(StatusLabel::fromString('закрыт'), false, true),
            ],
            [
                new NewWorkflowTransition(
                    TransitionName::fromString('закрыть'),
                    StatusLabel::fromString('открыт'),
                    StatusLabel::fromString('закрыт'),
                ),
            ],
            true,
        );

        return $this->dataMapper->workflowToResponse($workflow);
    }

    public function getDefaultForUser(string $userId): ?WorkflowResponseInterface
    {
        $workflow = $this->workflows->findDefaultByCreatedBy($userId);

        return $workflow !== null
            ? $this->dataMapper->workflowToResponse(
                $workflow,
                $this->descriptions->get(Workflow::class, $workflow->id()->value()),
            )
            : null;
    }

    public function update(
        string $id,
        UpdateWorkflowRequestInterface $request,
        string $userId,
    ): WorkflowResponseInterface {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $workflow = $this->updateInteractor->update(
            WorkflowId::fromString($id),
            $userId,
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

    public function getPage(
        int $limit,
        int $offset,
        string $userId,
        ?string $teamId = null,
        bool $includeDefault = true,
        ?string $inTeamId = null,
    ): array {
        $default = $includeDefault ? $this->workflows->findDefaultByCreatedBy($userId) : null;

        if ($offset === 0) {
            $othersLimit = $default !== null ? max(0, $limit - 1) : $limit;
            $own = $this->workflows->findByCreatedBy($userId, $othersLimit, 0);
            $page = $default !== null ? [$default, ...$own] : $own;
        } else {
            $adjustedOffset = $default !== null ? max(0, $offset - 1) : $offset;
            $page = $this->workflows->findByCreatedBy($userId, $limit, $adjustedOffset);
        }

        $teamWorkflowIds = [];
        $teamTitle = null;
        if ($teamId !== null) {
            $teamWorkflowIds = array_map(
                static fn(WorkflowTeam $link) => $link->workflowId()->value(),
                $this->workflowTeams->findByTeamId($teamId),
            );
            $teamTitle = $this->teamService->getById($teamId)?->getTitle();
        }

        if ($offset === 0 && $teamId !== null) {
            $ownIds = array_map(static fn($workflow) => $workflow->id()->value(), $page);
            $additionalIds = array_values(array_diff($teamWorkflowIds, $ownIds));
            $remaining = max(0, $limit - count($page));

            if ($remaining > 0 && $additionalIds !== []) {
                $page = [...$page, ...$this->workflows->findByIds(array_slice($additionalIds, 0, $remaining))];
            }
        }

        $inTeamWorkflowIds = [];
        if ($inTeamId !== null) {
            $inTeamWorkflowIds = array_map(
                static fn(WorkflowTeam $link) => $link->workflowId()->value(),
                $this->workflowTeams->findByTeamId($inTeamId),
            );
        }

        return array_map(
            fn($workflow) => $this->dataMapper->workflowToResponse(
                $workflow,
                null,
                in_array($workflow->id()->value(), $teamWorkflowIds, true) ? $teamTitle : null,
                in_array($workflow->id()->value(), $inTeamWorkflowIds, true),
            ),
            $page,
        );
    }

    public function countAll(string $userId, ?string $teamId = null, bool $includeDefault = true): int
    {
        $count = $this->workflows->countByCreatedBy($userId);

        if ($includeDefault && $this->workflows->findDefaultByCreatedBy($userId) !== null) {
            $count++;
        }

        if ($teamId !== null) {
            $teamWorkflows = $this->workflows->findByIds(array_map(
                static fn(WorkflowTeam $link) => $link->workflowId()->value(),
                $this->workflowTeams->findByTeamId($teamId),
            ));

            foreach ($teamWorkflows as $workflow) {
                if ($workflow->createdBy() !== $userId) {
                    $count++;
                }
            }
        }

        return $count;
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

    public function addStatus(
        string $workflowId,
        AddStatusRequestInterface $request,
        string $userId,
    ): WorkflowStatusResponseInterface {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $label = StatusLabel::fromString($request->getLabel());
        $status = $this->addStatusInteractor->add(
            WorkflowId::fromString($workflowId),
            $userId,
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
        string $userId,
    ): WorkflowStatusResponseInterface {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $status = $this->updateStatusInteractor->update(
            WorkflowId::fromString($workflowId),
            $userId,
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

    public function getStatusLabelsByIds(array $statusIds): array
    {
        $labels = [];
        foreach ($this->statuses->findByIds($statusIds) as $status) {
            $labels[$status->id()->value()] = $status->label()->value();
        }

        return $labels;
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
        string $userId,
    ): WorkflowTransitionResponseInterface {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $transition = $this->addTransitionInteractor->add(
            WorkflowId::fromString($workflowId),
            $userId,
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
        string $userId,
    ): WorkflowTransitionResponseInterface {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }

        $transition = $this->updateTransitionInteractor->update(
            WorkflowId::fromString($workflowId),
            $userId,
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

    public function listTransactionByWorkflow(string $workflowId): WorkflowListMiniResponseInterface
    {
        return $this->dataMapper->transitionsToWorkflowListMini(
            $this->transitions->findByWorkflowId(WorkflowId::fromString($workflowId)),
        );
    }

    public function attachToTeam(string $workflowId, string $teamId, string $userId): void
    {
        $this->attachToTeamInteractor->attach(WorkflowId::fromString($workflowId), $teamId, $userId);
    }

    public function detachFromTeam(string $workflowId, string $teamId, string $userId): void
    {
        $this->detachFromTeamInteractor->detach(WorkflowId::fromString($workflowId), $teamId, $userId);
    }

    public function getTeamWorkflows(string $teamId): array
    {
        $links = $this->workflowTeams->findByTeamId($teamId);

        if ($links === []) {
            return [];
        }

        $workflowIds = array_map(static fn(WorkflowTeam $link) => $link->workflowId()->value(), $links);

        $workflowsById = [];
        foreach ($this->workflows->findByIds($workflowIds) as $workflow) {
            $workflowsById[$workflow->id()->value()] = $workflow;
        }

        $taskCounts = $this->taskWorkflowUsage->countByWorkflowIds($workflowIds, $teamId);

        $result = [];
        foreach ($links as $link) {
            $workflow = $workflowsById[$link->workflowId()->value()] ?? null;

            if ($workflow === null) {
                continue;
            }

            $result[] = $this->dataMapper->workflowToTeamWorkflowResponse(
                $workflow,
                $link->attachedAt(),
                $taskCounts[$workflow->id()->value()] ?? 0,
            );
        }

        usort($result, static fn($a, $b) => $b->getAttachedAt() <=> $a->getAttachedAt());

        return $result;
    }

    public function getWorkflowTeams(string $workflowId): array
    {
        $links = $this->workflowTeams->findByWorkflowId(WorkflowId::fromString($workflowId));

        $result = array_map(
            fn(WorkflowTeam $link) => $this->dataMapper->linkToAttachedTeamResponse(
                $link->teamId(),
                $this->teamService->getById($link->teamId())?->getTitle(),
                $link->attachedAt(),
            ),
            $links,
        );

        usort($result, static fn($a, $b) => $b->getAttachedAt() <=> $a->getAttachedAt());

        return $result;
    }
}
