<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Port\ClockInterface;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;

final class CreateWorkflowInteractor
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function create(WorkflowTitle $title, string $createdBy): Workflow
    {
        $id = WorkflowId::generate();
        $workflow = Workflow::create($id, $title, $createdBy, $this->clock->now());

        $this->workflows->save($workflow);
        $this->eventDispatcher->dispatch(...$workflow->pullDomainEvents());

        return $workflow;
    }
}
