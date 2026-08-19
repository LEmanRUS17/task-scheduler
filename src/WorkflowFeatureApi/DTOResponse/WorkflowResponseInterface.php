<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTOResponse;

interface WorkflowResponseInterface
{
    public function getId(): string;

    public function getTitle(): string;

    public function getCreatedBy(): string;

    public function getCreatedAt(): \DateTimeImmutable;

    public function isDefault(): bool;

    public function getDescription(): ?string;

    /** Name of the team this workflow was returned for, when obtained via team attachment. */
    public function getTeamTitle(): ?string;

    /** Whether this workflow is attached to the team requested via $inTeamId in {@see WorkflowServiceInterface::getPage()}. */
    public function isInTeam(): bool;
}
