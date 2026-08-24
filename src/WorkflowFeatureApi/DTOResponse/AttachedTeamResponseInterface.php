<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTOResponse;

interface AttachedTeamResponseInterface
{
    public function getTeamId(): string;

    /** Null when the team no longer exists but the attachment link still does. */
    public function getTeamTitle(): ?string;

    public function getAttachedAt(): \DateTimeImmutable;
}
