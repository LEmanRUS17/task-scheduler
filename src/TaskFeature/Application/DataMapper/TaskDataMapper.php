<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DataMapper;

use App\TaskFeature\Application\DTOResponse\TaskResponseDTO;
use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use App\TaskFeatureApi\DTORequest\TaskCreateRequestInterface;

final class TaskDataMapper
{
    public function requestToTitle(TaskCreateRequestInterface $request): TaskTitle
    {
        return TaskTitle::fromString($request->getTitle());
    }

    public function requestToPriority(TaskCreateRequestInterface $request): TaskPriority
    {
        return $request->getPriority() !== null
            ? TaskPriority::from($request->getPriority())
            : TaskPriority::NO_PRIORITY;
    }

    /**
     * @param string[] $assigneeIds
     */
    public function taskToResponse(Task $task, array $assigneeIds): TaskResponseDTO
    {
        return new TaskResponseDTO(
            $task->id()->value(),
            $task->title()->value(),
            $task->getWorkflowStatus(),
            $task->priority()->value,
            $task->teamId(),
            $task->createdBy(),
            $assigneeIds,
            $task->createdAt(),
            $task->scheduledStart(),
            $task->scheduledEnd(),
            $task->estimatedTime(),
            $task->actualTime(),
        );
    }
}
