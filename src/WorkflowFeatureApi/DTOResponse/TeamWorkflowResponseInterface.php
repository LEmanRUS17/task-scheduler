<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTOResponse;

interface TeamWorkflowResponseInterface
{
    public function getWorkflowId(): string;

    public function getTitle(): string;

    /** Id of the user who attached the workflow to the team (always its owner, see {@see \App\WorkflowFeatureApi\Service\WorkflowServiceInterface::attachToTeam()}). */
    public function getAttachedBy(): string;

    public function getAttachedAt(): \DateTimeImmutable;

    /** Number of the team's tasks currently using this workflow. */
    public function getTaskCount(): int;
}
