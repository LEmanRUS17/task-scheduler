<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Port\ClockInterface;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;

final class AddWorkflowStatusInteractor
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function add(WorkflowId $workflowId, StatusLabel $label, bool $isInitial): WorkflowStatus
    {
        if ($this->workflows->findById($workflowId) === null) {
            throw new \DomainException("Workflow \"{$workflowId->value()}\" not found");
        }

        if ($this->statuses->findByLabel($workflowId, $label->value()) !== null) {
            throw new \DomainException("Status \"{$label->value()}\" already exists in this workflow");
        }

        if ($isInitial && $this->statuses->hasInitial($workflowId)) {
            throw new \DomainException('Workflow already has an initial status');
        }

        $status = WorkflowStatus::add(
            WorkflowStatusId::generate(),
            $workflowId,
            $label,
            $isInitial,
            $this->clock->now(),
        );

        $this->statuses->save($status);
        $this->eventDispatcher->dispatch(...$status->pullDomainEvents());

        return $status;
    }
}
