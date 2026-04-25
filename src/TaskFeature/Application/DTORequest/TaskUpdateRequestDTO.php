<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DTORequest;

use App\TaskFeatureApi\DTORequest\TaskUpdateRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TaskUpdateRequestDTO implements TaskUpdateRequestInterface
{
    public function __construct(
        #[Assert\Length(max: 255, maxMessage: 'Title must not exceed 255 characters')]
        private readonly ?string $title = null,

        #[Assert\Choice(
            choices: ['no_priority', 'low', 'normal', 'high', 'critical'],
            message: 'Invalid priority value',
        )]
        private readonly ?string $priority = null,

        private readonly ?\DateTimeImmutable $scheduledStart = null,

        private readonly ?\DateTimeImmutable $scheduledEnd = null,

        #[Assert\PositiveOrZero(message: 'Estimated time must be a non-negative integer')]
        private readonly ?int $estimatedTime = null,
    ) {}

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
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
