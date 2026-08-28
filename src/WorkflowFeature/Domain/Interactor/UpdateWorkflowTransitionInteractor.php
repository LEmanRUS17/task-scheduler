<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Exception\WorkflowAccessDeniedException;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
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
        string $userId,
        WorkflowTransitionId $transitionId,
        TransitionName $name,
        WorkflowStatusId $fromStatusId,
        WorkflowStatusId $toStatusId,
    ): WorkflowTransition {
        $workflow = $this->workflows->findById($workflowId);

        if ($workflow === null) {
            throw new \DomainException("Workflow \"{$workflowId->value()}\" not found");
        }

        if ($workflow->createdBy() !== $userId) {
            throw WorkflowAccessDeniedException::notOwner($workflowId->value());
        }

        if ($workflow->isDefault()) {
            throw WorkflowAccessDeniedException::isDefaultWorkflow($workflowId->value());
        }

        $transition = $this->transitions->findById($transitionId);

        if ($transition === null || $transition->workflowId()->value() !== $workflowId->value()) {
            throw new \DomainException("Transition \"{$transitionId->value()}\" not found in this workflow");
        }

        if ($this->statuses->findById($workflowId, $fromStatusId->value()) === null) {
            throw new \DomainException("Status \"{$fromStatusId->value()}\" not found in this workflow");
        }

        if ($this->statuses->findById($workflowId, $toStatusId->value()) === null) {
            throw new \DomainException("Status \"{$toStatusId->value()}\" not found in this workflow");
        }

        if (
            $name->value() !== $transition->name()->value()
            && $this->transitions->existsByName($workflowId, $name->value())
        ) {
            throw new \DomainException("Transition \"{$name->value()}\" already exists in this workflow");
        }

        $transition->update($name, $fromStatusId, $toStatusId);

        $this->transitions->save($transition);
        $this->eventDispatcher->dispatch(...$transition->pullDomainEvents());

        return $transition;
    }
}
