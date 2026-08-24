<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Exception\WorkflowAccessDeniedException;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;

final class UpdateWorkflowInteractor
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function update(WorkflowId $workflowId, string $userId, WorkflowTitle $title): Workflow
    {
        $workflow = $this->workflows->findById($workflowId);

        if ($workflow === null) {
            throw new \DomainException("Workflow \"{$workflowId->value()}\" not found");
        }

        if ($workflow->createdBy() !== $userId) {
            throw WorkflowAccessDeniedException::notOwner($workflowId->value());
        }

        $workflow->updateTitle($title);

        $this->workflows->save($workflow);
        $this->eventDispatcher->dispatch(...$workflow->pullDomainEvents());

        return $workflow;
    }
}
