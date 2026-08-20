<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTOResponse;

use App\WorkflowFeatureApi\DTOResponse\TeamWorkflowResponseInterface;

final class TeamWorkflowResponseDTO implements TeamWorkflowResponseInterface
{
    public function __construct(
        private readonly string $workflowId,
        private readonly string $title,
        private readonly string $attachedBy,
        private readonly \DateTimeImmutable $attachedAt,
        private readonly int $taskCount,
    ) {
    }

    public function getWorkflowId(): string
    {
        return $this->workflowId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAttachedBy(): string
    {
        return $this->attachedBy;
    }

    public function getAttachedAt(): \DateTimeImmutable
    {
        return $this->attachedAt;
    }

    public function getTaskCount(): int
    {
        return $this->taskCount;
    }
}
