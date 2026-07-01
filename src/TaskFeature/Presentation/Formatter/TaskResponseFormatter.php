<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Formatter;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
use App\TagFeatureApi\DTOResponse\TagResponseInterface;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;

final class TaskResponseFormatter
{
    /**
     * @param list<TagResponseInterface> $tags
     * @return array<string, mixed>
     */
    public static function format(TaskDataResponseInterface $task, array $tags = []): array
    {
        $assigneeProfiles = $task->getAssigneeProfiles();

        return [
            'tags' => array_map(
                static fn(TagResponseInterface $tag): array => [
                    'id' => $tag->getId(),
                    'name' => $tag->getName(),
                    'color' => $tag->getColor(),
                ],
                $tags,
            ),
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'status' => $task->getStatus(),
            'status_id' => $task->getStatusId(),
            'priority' => $task->getPriority(),
            'teamId' => $task->getTeamId(),
            'createdBy' => $task->getCreatedBy(),
            'createdByUser' => self::user($task->getCreatedBy(), $task->getCreatedByProfile()),
            'assigneeIds' => $task->getAssigneeIds(),
            'assignees' => array_map(
                static fn(string $userId): array => self::user($userId, $assigneeProfiles[$userId] ?? null),
                $task->getAssigneeIds(),
            ),
            'scheduledStart' => $task->getScheduledStart()?->format(\DateTimeInterface::ATOM),
            'scheduledEnd' => $task->getScheduledEnd()?->format(\DateTimeInterface::ATOM),
            'estimatedTime' => $task->getEstimatedTime(),
            'actualTime' => $task->getActualTime(),
            'createdAt' => $task->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'availableTransitions' => $task->getAvailableTransitions(),
            'description' => $task->getDescription(),
        ];
    }

    /** @return array<string, mixed> */
    private static function user(string $userId, ?ProfileDataResponseInterface $profile): array
    {
        return [
            'userId' => $userId,
            'username' => $profile?->getUsername(),
            'firstname' => $profile?->getFirstname(),
            'lastname' => $profile?->getLastname(),
            'midlname' => $profile?->getMidlname(),
            'status' => $profile?->getStatus(),
            'avatar' => $profile?->getAvatar()?->getUrl(),
        ];
    }
}
