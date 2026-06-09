<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DTOResponse;

use App\TaskFeatureApi\DTOResponse\TaskStatusHistoryResponseInterface;

final class TaskStatusHistoryResponseDTO implements TaskStatusHistoryResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $transitionId,
        private readonly ?string $changedBy,
        private readonly \DateTimeImmutable $changedAt,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTransitionId(): string
    {
        return $this->transitionId;
    }

    public function getChangedBy(): ?string
    {
        return $this->changedBy;
    }

    public function getChangedAt(): \DateTimeImmutable
    {
        return $this->changedAt;
    }
}
