<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DTOResponse;

use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;

final class TaskResponseDTO implements TaskDataResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $status,
        private readonly string $priority,
        private readonly string $createdBy,
        private readonly \DateTimeImmutable $createdAt,
        private readonly ?\DateTimeImmutable $scheduledStart,
        private readonly ?\DateTimeImmutable $scheduledEnd,
        private readonly ?int $estimatedTime,
        private readonly ?int $actualTime,
    ) {}

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

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
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
}
