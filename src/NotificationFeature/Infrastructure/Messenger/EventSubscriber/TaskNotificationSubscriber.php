<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\EventSubscriber;

use App\TaskFeature\Domain\Event\TaskAssigneeAdded;
use App\TaskFeature\Domain\Event\TaskCreated;
use App\TaskFeature\Domain\Event\TaskStatusChanged;
use App\TaskFeature\Infrastructure\Messenger\Message\TaskAssigneeAddedMessage;
use App\TaskFeature\Infrastructure\Messenger\Message\TaskCreatedMessage;
use App\TaskFeature\Infrastructure\Messenger\Message\TaskStatusChangedMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final class TaskNotificationSubscriber
{
    public function __construct(
        private readonly MessageBusInterface $defaultBus,
    ) {}

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskStatusChanged(TaskStatusChanged $event): void
    {
        $this->defaultBus->dispatch(new TaskStatusChangedMessage(
            taskId: $event->taskId,
            fromStatus: $event->fromStatus,
            toStatus: $event->toStatus,
            workflowDefinitionTitle: $event->workflowDefinitionTitle,
            teamId: $event->teamId,
        ));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskCreated(TaskCreated $event): void
    {
        $this->defaultBus->dispatch(new TaskCreatedMessage(
            taskId: $event->id->value(),
            title: $event->title->value(),
            createdBy: $event->createdBy,
        ));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskAssigneeAdded(TaskAssigneeAdded $event): void
    {
        $this->defaultBus->dispatch(new TaskAssigneeAddedMessage(
            taskId: $event->taskId->value(),
            userId: $event->userId,
        ));
    }
}
