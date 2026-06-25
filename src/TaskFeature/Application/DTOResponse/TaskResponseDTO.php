<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DTOResponse;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;

final class TaskResponseDTO implements TaskDataResponseInterface
{
    /**
     * @param string[] $assigneeIds
     * @param string[] $availableTransitions
     * @param array<string, ProfileDataResponseInterface> $assigneeProfiles
     */
    public function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $status,
        private readonly string $statusId,
        private readonly string $priority,
        private readonly ?string $teamId,
        private readonly string $createdBy,
        private readonly array $assigneeIds,
        private readonly \DateTimeImmutable $createdAt,
        private readonly ?\DateTimeImmutable $scheduledStart,
        private readonly ?\DateTimeImmutable $scheduledEnd,
        private readonly ?int $estimatedTime,
        private readonly ?int $actualTime,
        private readonly array $availableTransitions,
        private readonly ?string $description = null,
        private readonly ?ProfileDataResponseInterface $createdByProfile = null,
        private readonly array $assigneeProfiles = [],
    ) {
        return;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusId(): string
    {
        return $this->statusId;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getTeamId(): ?string
    {
        return $this->teamId;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function getCreatedByProfile(): ?ProfileDataResponseInterface
    {
        return $this->createdByProfile;
    }

    public function getAssigneeIds(): array
    {
        return $this->assigneeIds;
    }

    public function getAssigneeProfiles(): array
    {
        return $this->assigneeProfiles;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getScheduledStart(): ?\DateTimeImmutable
    {
        return $this->scheduledStart;
    }

    public function getScheduledEnd(): ?\DateTimeImmutable
    {
        return $this->scheduledEnd;
    }

    public function getEstimatedTime(): ?int
    {
        return $this->estimatedTime;
    }

    public function getActualTime(): ?int
    {
        return $this->actualTime;
    }

    public function getAvailableTransitions(): array
    {
        return $this->availableTransitions;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
