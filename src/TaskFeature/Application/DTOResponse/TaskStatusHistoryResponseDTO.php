<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DTOResponse;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
use App\TaskFeatureApi\DTOResponse\TaskStatusHistoryResponseInterface;

final class TaskStatusHistoryResponseDTO implements TaskStatusHistoryResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $transitionId,
        private readonly ?string $toStatusLabel,
        private readonly ?ProfileDataResponseInterface $changedByProfile,
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

    public function getToStatusLabel(): ?string
    {
        return $this->toStatusLabel;
    }

    public function getProfile(): ?ProfileDataResponseInterface
    {
        return $this->changedByProfile;
    }

    public function getChangedAt(): \DateTimeImmutable
    {
        return $this->changedAt;
    }
}
