<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Application\DTOResponse;

use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;

final class WorkflowResponseDTO implements WorkflowResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $createdBy,
        private readonly \DateTimeImmutable $createdAt,
        private readonly bool $isDefault = false,
        private readonly ?string $description = null,
        private readonly ?string $teamTitle = null,
        private readonly bool $inTeam = false,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTeamTitle(): ?string
    {
        return $this->teamTitle;
    }

    public function isInTeam(): bool
    {
        return $this->inTeam;
    }
}
