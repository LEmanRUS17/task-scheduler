<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\ApiService;

use App\TaskFeature\Application\DataMapper\TaskDataMapper;
use App\TaskFeature\Application\DTORequestValidator\TaskValidatorInterface;
use App\TaskFeature\Domain\Interactor\AddTaskAssigneeInteractor;
use App\TaskFeature\Domain\Interactor\ApplyTaskTransitionInteractor;
use App\TaskFeature\Domain\Interactor\CreateTaskInteractor;
use App\TaskFeature\Domain\Interactor\RemoveTaskAssigneeInteractor;
use App\TaskFeature\Domain\Interactor\UpdateTaskInteractor;
use App\TaskFeature\Domain\Repository\TaskAssigneeRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use App\TaskFeatureApi\DTORequest\TaskCreateRequestInterface;
use App\TaskFeatureApi\DTORequest\TaskUpdateRequestInterface;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;

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
        private readonly TaskDataMapper $dataMapper,
        private readonly TaskValidatorInterface $validator,
    ) {}

    public function getList(string $userId): array
    {
        return array_map(
            fn($task) => $this->dataMapper->taskToResponse($task, $this->loadAssigneeIds($task->id())),
            $this->tasks->findByAssigneeUserId($userId),
        );
    }

    public function getById(string $id): ?TaskDataResponseInterface
    {
        $taskId = TaskId::fromString($id);
        $task = $this->tasks->findById($taskId);

        return $task !== null
            ? $this->dataMapper->taskToResponse($task, $this->loadAssigneeIds($taskId))
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

        return $this->dataMapper->taskToResponse($task, $this->loadAssigneeIds($task->id()));
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

        return $this->dataMapper->taskToResponse($task, $this->loadAssigneeIds($task->id()));
    }

    public function applyTransition(string $id, string $transition): TaskDataResponseInterface
    {
        $task = $this->transitionInteractor->apply($id, $transition);

        return $this->dataMapper->taskToResponse($task, $this->loadAssigneeIds($task->id()));
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
