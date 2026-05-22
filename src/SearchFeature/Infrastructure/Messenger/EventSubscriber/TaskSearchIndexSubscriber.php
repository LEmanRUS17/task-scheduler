<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\EventSubscriber;

use App\SearchFeature\Infrastructure\Messenger\Message\IndexTaskMessage;
use App\TaskFeature\Domain\Event\TaskCreated;
use App\TaskFeature\Domain\Event\TaskStatusChanged;
use App\TaskFeature\Domain\Event\TaskUpdated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final class TaskSearchIndexSubscriber
{
    public function __construct(private readonly MessageBusInterface $defaultBus)
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskCreated(TaskCreated $event): void
    {
        $this->defaultBus->dispatch(new IndexTaskMessage($event->id->value()));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskUpdated(TaskUpdated $event): void
    {
        $this->defaultBus->dispatch(new IndexTaskMessage($event->taskId));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskStatusChanged(TaskStatusChanged $event): void
    {
        $this->defaultBus->dispatch(new IndexTaskMessage($event->taskId));
    }
}
