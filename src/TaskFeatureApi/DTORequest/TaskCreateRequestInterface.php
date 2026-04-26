<?php

declare(strict_types=1);

namespace App\TaskFeatureApi\DTORequest;

interface TaskCreateRequestInterface
{
    public function getTitle(): string;

    public function getPriority(): ?string;

    public function getWorkflow(): string;

    public function getTeamId(): ?string;

    public function getAssigneeId(): ?string;

    public function getScheduledStart(): ?\DateTimeImmutable;

    public function getScheduledEnd(): ?\DateTimeImmutable;

    public function getEstimatedTime(): ?int;
}
