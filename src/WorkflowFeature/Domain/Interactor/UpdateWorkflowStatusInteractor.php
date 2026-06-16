<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;

final class UpdateWorkflowStatusInteractor
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStatusRepositoryInterface $statuses,
    ) {
    }

    public function update(WorkflowId $workflowId, StatusLabel $label): WorkflowStatus
    {
        if ($this->workflows->findById($workflowId) === null) {
            throw new \DomainException("Workflow \"{$workflowId->value()}\" not found");
        }

        $status = $this->statuses->findByLabel($workflowId, $label->value());

        if ($status === null) {
            throw new \DomainException("Status \"{$label->value()}\" not found in this workflow");
        }

        return $status;
    }
}
