<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTOResponse;

use App\WorkflowFeatureApi\DTOResponse\AttachedTeamResponseInterface;

final class AttachedTeamResponseDTO implements AttachedTeamResponseInterface
{
    public function __construct(
        private readonly string $teamId,
        private readonly ?string $teamTitle,
        private readonly \DateTimeImmutable $attachedAt,
    ) {
    }

    public function getTeamId(): string
    {
        return $this->teamId;
    }

    public function getTeamTitle(): ?string
    {
        return $this->teamTitle;
    }

    public function getAttachedAt(): \DateTimeImmutable
    {
        return $this->attachedAt;
    }
}
