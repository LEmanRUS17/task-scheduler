<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DataMapper;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
use App\TaskFeature\Application\DTOResponse\TaskResponseDTO;
use App\TaskFeature\Application\DTOResponse\TaskStatusHistoryResponseDTO;
use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Entity\TaskStatusHistory;
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
     * @param string[] $availableTransitions
     * @param array<string, ProfileDataResponseInterface> $assigneeProfiles
     */
    public function taskToResponse(
        Task $task,
        array $assigneeIds,
        array $availableTransitions,
        ?string $statusLabel = null,
        ?string $description = null,
        ?ProfileDataResponseInterface $createdByProfile = null,
        array $assigneeProfiles = [],
    ): TaskResponseDTO {
        return new TaskResponseDTO(
            $task->id()->value(),
            $task->title()->value(),
            $statusLabel ?? $task->getWorkflowStatus(),
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
            $availableTransitions,
            $description,
            $createdByProfile,
            $assigneeProfiles,
        );
    }

    public function historyToResponse(
        TaskStatusHistory $entry,
        ?string $toStatusLabel,
        ?ProfileDataResponseInterface $changedByProfile,
    ): TaskStatusHistoryResponseDTO {
        return new TaskStatusHistoryResponseDTO(
            $entry->id(),
            $entry->transitionId(),
            $toStatusLabel,
            $changedByProfile,
            $entry->changedAt(),
        );
    }
}
