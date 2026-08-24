<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTeamRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

final class DetachWorkflowFromTeamInteractor
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowTeamRepositoryInterface $workflowTeams,
    ) {
    }

    public function detach(WorkflowId $workflowId, string $teamId, string $userId): void
    {
        $workflow = $this->workflows->findById($workflowId);

        if ($workflow === null) {
            throw new \DomainException("Workflow {$workflowId->value()} not found");
        }

        if ($workflow->createdBy() !== $userId) {
            throw new \DomainException('Only the workflow owner can detach it from a team');
        }

        $link = $this->workflowTeams->findByWorkflowIdAndTeamId($workflowId, $teamId);

        if ($link === null) {
            throw new \DomainException('Workflow is not attached to this team');
        }

        $this->workflowTeams->delete($link);
    }
}
