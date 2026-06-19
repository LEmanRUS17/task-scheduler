<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\EventSubscriber;

use App\SearchFeature\Infrastructure\Messenger\Message\IndexWorkflowMessage;
use App\WorkflowFeature\Domain\Event\WorkflowCreated;
use App\WorkflowFeature\Domain\Event\WorkflowUpdated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final class WorkflowSearchIndexSubscriber
{
    public function __construct(private readonly MessageBusInterface $defaultBus)
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onWorkflowCreated(WorkflowCreated $event): void
    {
        $this->defaultBus->dispatch(new IndexWorkflowMessage($event->id->value()));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onWorkflowUpdated(WorkflowUpdated $event): void
    {
        $this->defaultBus->dispatch(new IndexWorkflowMessage($event->id->value()));
    }
}
