<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\ApiService;

use App\TaskFeature\Application\DataMapper\TaskDataMapper;
use App\TaskFeature\Application\DTORequestValidator\TaskValidatorInterface;
use App\TaskFeature\Domain\Interactor\AddTaskAssigneeInteractor;
use App\TaskFeature\Domain\Interactor\ApplyTaskTransitionInteractor;
use App\TaskFeature\Domain\Interactor\CreateTaskInteractor;
use App\TaskFeature\Domain\Interactor\RemoveTaskAssigneeInteractor;
use App\TaskFeature\Domain\Event\TaskDeleted;
use App\TaskFeature\Domain\Interactor\UpdateTaskInteractor;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Port\TeamMembershipInterface;
use App\TaskFeature\Domain\Port\TaskWorkflowInterface;
use App\TaskFeature\Domain\Repository\TaskAssigneeRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskStatusHistoryRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use App\TaskFeatureApi\DTORequest\TaskCreateRequestInterface;
use App\TaskFeatureApi\DTORequest\TaskUpdateRequestInterface;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\TaskFeature\Domain\Entity\Task;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;

final class TaskApiService implements TaskServiceInterface
{
    public function __construct(
        private readonly CreateTaskInteractor $createInteractor,
        private readonly UpdateTaskInteractor $updateInteractor,
        private readonly ApplyTaskTransitionInteractor $transitionInteractor,
        private readonly AddTaskAssigneeInteractor $addAssigneeInteractor,
        private readonly RemoveTaskAssigneeInteractor $removeAssigneeInteractor,
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskAssigneeRepositoryInterface $assignees,
        private readonly TaskStatusHistoryRepositoryInterface $statusHistory,
        private readonly WorkflowTransitionRepositoryInterface $transitions,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly ProfileServiceInterface $profiles,
        private readonly TaskDataMapper $dataMapper,
        private readonly TaskValidatorInterface $validator,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly TaskWorkflowInterface $workflow,
        private readonly TeamMembershipInterface $teamMembership,
        private readonly DescriptionServiceInterface $descriptions,
    ) {
    }

    public function getAll(): array
    {
        return array_map(
            fn($task) => $this->dataMapper->taskToResponse(
                $task,
                $this->loadAssigneeIds($task->id()),
                [],
                $this->resolveStatusLabel($task),
                $this->descriptions->get(Task::class, $task->id()->value()),
            ),
            $this->tasks->findAll(),
        );
    }

    public function getList(string $userId): array
    {
        return array_map(
            fn($task) => $this->dataMapper->taskToResponse(
                $task,
                $this->loadAssigneeIds($task->id()),
                $this->workflow->getEnabledTransitions($task),
                $this->resolveStatusLabel($task),
                $this->descriptions->get(Task::class, $task->id()->value()),
            ),
            $this->tasks->findByAssigneeUserId($userId),
        );
    }

    public function getListByTeam(string $teamId, string $userId): array
    {
        if (!$this->teamMembership->isMember($teamId, $userId)) {
            throw new \DomainException('Access denied: user is not a member of the team.');
        }

        return array_map(
            fn($task) => $this->dataMapper->taskToResponse(
                $task,
                $this->loadAssigneeIds($task->id()),
                $this->workflow->getEnabledTransitions($task),
                $this->resolveStatusLabel($task),
                $this->descriptions->get(Task::class, $task->id()->value()),
            ),
            $this->tasks->findByTeamId($teamId),
        );
    }

    public function getById(string $id): ?TaskDataResponseInterface
    {
        $taskId = TaskId::fromString($id);
        $task = $this->tasks->findById($taskId);

        return $task !== null
            ? $this->dataMapper->taskToResponse(
                $task,
                $this->loadAssigneeIds($taskId),
                $this->workflow->getEnabledTransitions($task),
                $this->resolveStatusLabel($task),
                $this->descriptions->get(Task::class, $id),
            )
            : null;
    }

    public function create(TaskCreateRequestInterface $dtoRequest, string $creatorUserId): TaskDataResponseInterface
    {
        $violations = $this->validator->validate($dtoRequest, $creatorUserId);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations));
        }

        $task = $this->createInteractor->create(
            $this->dataMapper->requestToTitle($dtoRequest),
            $this->dataMapper->requestToPriority($dtoRequest),
            $dtoRequest->getWorkflow(),
            $dtoRequest->getTeamId(),
            $creatorUserId,
            $dtoRequest->getAssigneeIds(),
            $dtoRequest->getScheduledStart(),
            $dtoRequest->getScheduledEnd(),
            $dtoRequest->getEstimatedTime(),
        );

        $description = $dtoRequest->getDescription();
        if ($description !== null) {
            $this->descriptions->set(Task::class, $task->id()->value(), $description);
        }

        return $this->dataMapper->taskToResponse(
            $task,
            $this->loadAssigneeIds($task->id()),
            $this->workflow->getEnabledTransitions($task),
            $this->resolveStatusLabel($task),
            $description,
        );
    }

    public function update(string $id, TaskUpdateRequestInterface $dtoRequest): TaskDataResponseInterface
    {
        $violations = $this->validator->validateUpdate($dtoRequest);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations));
        }

        $task = $this->updateInteractor->update(
            $id,
            $dtoRequest->getTitle() !== null ? TaskTitle::fromString($dtoRequest->getTitle()) : null,
            $dtoRequest->getPriority() !== null ? TaskPriority::from($dtoRequest->getPriority()) : null,
            $dtoRequest->getScheduledStart(),
            $dtoRequest->getScheduledEnd(),
            $dtoRequest->getEstimatedTime(),
        );

        $description = $dtoRequest->getDescription();
        if ($description !== null) {
            $this->descriptions->set(Task::class, $id, $description);
        }

        return $this->dataMapper->taskToResponse(
            $task,
            $this->loadAssigneeIds($task->id()),
            $this->workflow->getEnabledTransitions($task),
            $this->resolveStatusLabel($task),
            $this->descriptions->get(Task::class, $id),
        );
    }

    public function applyTransition(string $id, string $transition): TaskDataResponseInterface
    {
        $task = $this->transitionInteractor->apply($id, $transition);

        return $this->dataMapper->taskToResponse(
            $task,
            $this->loadAssigneeIds($task->id()),
            $this->workflow->getEnabledTransitions($task),
            $this->resolveStatusLabel($task),
            $this->descriptions->get(Task::class, $id),
        );
    }

    public function addAssignee(string $taskId, string $userId): void
    {
        $this->addAssigneeInteractor->add(TaskId::fromString($taskId), $userId);
    }

    public function removeAssignee(string $taskId, string $userId): void
    {
        $this->removeAssigneeInteractor->remove(TaskId::fromString($taskId), $userId);
    }

    public function deleteById(string $id): void
    {
        $task = $this->tasks->findById(TaskId::fromString($id));

        if ($task === null) {
            throw new \DomainException("Task {$id} not found");
        }

        $taskId = TaskId::fromString($id);
        $this->assignees->deleteByTaskId($taskId);
        $this->tasks->delete($taskId);
        $this->descriptions->delete(Task::class, $id);
        $this->eventDispatcher->dispatch(new TaskDeleted($id));
    }

    public function getStatusHistory(string $taskId): array
    {
        return array_map(function ($entry) {
            $transition = $this->transitions->findById(WorkflowTransitionId::fromString($entry->transitionId()));

            $toStatusLabel = null;
            if ($transition !== null) {
                $toStatus = $this->statuses->findById(
                    $transition->workflowId(),
                    $transition->toStatusId()->value(),
                );
                $toStatusLabel = $toStatus?->label()->value();
            }

            $profile = null;
            if ($entry->changedBy() !== null) {
                try {
                    $profile = $this->profiles->getByUserId($entry->changedBy());
                } catch (\DomainException) {
                }
            }

            return $this->dataMapper->historyToResponse($entry, $toStatusLabel, $profile);
        }, $this->statusHistory->findByTaskId($taskId));
    }

    private function resolveStatusLabel(Task $task): ?string
    {
        $statusId = $task->getWorkflowStatus();

        if ($statusId === '') {
            return null;
        }

        $status = $this->statuses->findById(
            WorkflowId::fromString($task->getWorkflowDefinitionTitle()),
            $statusId,
        );

        return $status?->label()->value();
    }

    /** @return string[] */
    private function loadAssigneeIds(TaskId $taskId): array
    {
        return array_map(
            fn($a) => $a->userId(),
            $this->assignees->findByTaskId($taskId),
        );
    }
}
