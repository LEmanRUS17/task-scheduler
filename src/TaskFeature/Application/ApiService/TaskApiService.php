<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\ApiService;

use App\TaskFeature\Application\DataMapper\TaskDataMapper;
use App\TaskFeature\Application\DTORequestValidator\TaskValidatorInterface;
use App\TaskFeature\Domain\Interactor\ApplyTaskTransitionInteractor;
use App\TaskFeature\Domain\Interactor\CreateTaskInteractor;
use App\TaskFeature\Domain\Interactor\UpdateTaskInteractor;
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
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskDataMapper $dataMapper,
        private readonly TaskValidatorInterface $validator,
    ) {}

    public function getList(): array
    {
        return array_map(
            fn($task) => $this->dataMapper->taskToResponse($task),
            $this->tasks->findAll(),
        );
    }

    public function getById(string $id): ?TaskDataResponseInterface
    {
        $task = $this->tasks->findById(TaskId::fromString($id));

        return $task !== null ? $this->dataMapper->taskToResponse($task) : null;
    }

    public function create(TaskCreateRequestInterface $dtoRequest, string $creatorUserId): TaskDataResponseInterface
    {
        $violations = $this->validator->validate($dtoRequest);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations));
        }

        $task = $this->createInteractor->create(
            $this->dataMapper->requestToTitle($dtoRequest),
            $this->dataMapper->requestToPriority($dtoRequest),
            $dtoRequest->getWorkflow(),
            $dtoRequest->getTeamId(),
            $creatorUserId,
            $dtoRequest->getScheduledStart(),
            $dtoRequest->getScheduledEnd(),
            $dtoRequest->getEstimatedTime(),
        );

        return $this->dataMapper->taskToResponse($task);
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

        return $this->dataMapper->taskToResponse($task);
    }

    public function applyTransition(string $id, string $transition): TaskDataResponseInterface
    {
        $task = $this->transitionInteractor->apply($id, $transition);

        return $this->dataMapper->taskToResponse($task);
    }

    public function deleteById(string $id): void
    {
        $task = $this->tasks->findById(TaskId::fromString($id));

        if ($task === null) {
            throw new \DomainException("Task {$id} not found");
        }

        $this->tasks->delete(TaskId::fromString($id));
    }
}
