<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;

final class UpdateWorkflowStatusInteractor
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function update(
        WorkflowId $workflowId,
        WorkflowStatusId $statusId,
        StatusLabel $label,
        ?bool $isFinal = null,
    ): WorkflowStatus {
        if ($this->workflows->findById($workflowId) === null) {
            throw new \DomainException("Workflow \"{$workflowId->value()}\" not found");
        }

        $status = $this->statuses->findById($workflowId, $statusId->value());

        if ($status === null) {
            throw new \DomainException("Status \"{$statusId->value()}\" not found in this workflow");
        }

        if ($label->value() !== $status->label()->value()) {
            $existing = $this->statuses->findByLabel($workflowId, $label->value());

            if ($existing !== null && $existing->id()->value() !== $status->id()->value()) {
                throw new \DomainException("Status \"{$label->value()}\" already exists in this workflow");
            }
        }

        $status->rename($label);

        if ($isFinal !== null) {
            $status->markFinal($isFinal);
        }

        $this->statuses->save($status);
        $this->eventDispatcher->dispatch(...$status->pullDomainEvents());

        return $status;
    }
}
