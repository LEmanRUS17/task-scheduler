<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;

final class UpdateWorkflowTransitionInteractor
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStatusRepositoryInterface $statuses,
        private readonly WorkflowTransitionRepositoryInterface $transitions,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function update(
        WorkflowId $workflowId,
        WorkflowTransitionId $transitionId,
        TransitionName $name,
        StatusLabel $fromStatusLabel,
        StatusLabel $toStatusLabel,
    ): WorkflowTransition {
        if ($this->workflows->findById($workflowId) === null) {
            throw new \DomainException("Workflow \"{$workflowId->value()}\" not found");
        }

        $transition = $this->transitions->findById($transitionId);

        if ($transition === null || $transition->workflowId()->value() !== $workflowId->value()) {
            throw new \DomainException("Transition \"{$transitionId->value()}\" not found in this workflow");
        }

        if ($this->statuses->findByLabel($workflowId, $fromStatusLabel->value()) === null) {
            throw new \DomainException("Status \"{$fromStatusLabel->value()}\" not found in this workflow");
        }

        if ($this->statuses->findByLabel($workflowId, $toStatusLabel->value()) === null) {
            throw new \DomainException("Status \"{$toStatusLabel->value()}\" not found in this workflow");
        }

        if (
            $name->value() !== $transition->name()->value()
            && $this->transitions->existsByName($workflowId, $name->value())
        ) {
            throw new \DomainException("Transition \"{$name->value()}\" already exists in this workflow");
        }

        $transition->update($name, $fromStatusLabel, $toStatusLabel);

        $this->transitions->save($transition);
        $this->eventDispatcher->dispatch(...$transition->pullDomainEvents());

        return $transition;
    }
}
