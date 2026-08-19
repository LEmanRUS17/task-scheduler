<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\WorkflowTeam;
use App\WorkflowFeature\Domain\Port\ClockInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTeamRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

final class AttachWorkflowToTeamInteractor
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowTeamRepositoryInterface $workflowTeams,
        private readonly ClockInterface $clock,
    ) {
    }

    public function attach(WorkflowId $workflowId, string $teamId, string $userId): WorkflowTeam
    {
        $workflow = $this->workflows->findById($workflowId);

        if ($workflow === null) {
            throw new \DomainException("Workflow {$workflowId->value()} not found");
        }

        if ($workflow->createdBy() !== $userId) {
            throw new \DomainException('Only the workflow owner can attach it to a team');
        }

        if ($workflow->isDefault()) {
            throw new \DomainException('Default workflow cannot be attached to a team');
        }

        $existing = $this->workflowTeams->findByWorkflowIdAndTeamId($workflowId, $teamId);

        if ($existing !== null) {
            return $existing;
        }

        $link = WorkflowTeam::attach($workflowId, $teamId, $this->clock->now());
        $this->workflowTeams->save($link);

        return $link;
    }
}
