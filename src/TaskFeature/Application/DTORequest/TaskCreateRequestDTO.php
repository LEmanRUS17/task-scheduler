<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DTORequest;

use App\TaskFeatureApi\DTORequest\TaskCreateRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TaskCreateRequestDTO implements TaskCreateRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Title is required')]
        #[Assert\Length(max: 255, maxMessage: 'Title must not exceed 255 characters')]
        private readonly string $title,

        #[Assert\NotBlank(message: 'Workflow is required')]
        private readonly string $workflow,

        #[Assert\Choice(
            choices: ['no_priority', 'low', 'normal', 'high', 'critical'],
            message: 'Invalid priority value',
        )]
        private readonly ?string $priority = null,

        private readonly ?string $teamId = null,

        private readonly ?string $assigneeId = null,

        private readonly ?\DateTimeImmutable $scheduledStart = null,

        private readonly ?\DateTimeImmutable $scheduledEnd = null,

        #[Assert\PositiveOrZero(message: 'Estimated time must be a non-negative integer')]
        private readonly ?int $estimatedTime = null,
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getWorkflow(): string
    {
        return $this->workflow;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function getTeamId(): ?string
    {
        return $this->teamId;
    }

    public function getAssigneeId(): ?string
    {
        return $this->assigneeId;
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
}
