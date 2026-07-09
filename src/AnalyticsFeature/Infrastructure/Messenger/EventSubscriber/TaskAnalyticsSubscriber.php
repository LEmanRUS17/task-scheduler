<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Infrastructure\Messenger\EventSubscriber;

use App\AnalyticsFeature\Infrastructure\Messenger\Message\RecordTaskActionMessage;
use App\AnalyticsFeature\Infrastructure\Messenger\Message\RecordTaskEventMessage;
use App\TaskFeature\Domain\Event\TaskAssigneeAdded;
use App\TaskFeature\Domain\Event\TaskAssigneeRemoved;
use App\TaskFeature\Domain\Event\TaskCreated;
use App\TaskFeature\Domain\Event\TaskDeleted;
use App\TaskFeature\Domain\Event\TaskStatusChanged;
use App\TaskFeature\Domain\Event\TaskUpdated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final class TaskAnalyticsSubscriber
{
    public function __construct(private readonly MessageBusInterface $defaultBus)
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskStatusChanged(TaskStatusChanged $event): void
    {
        $this->defaultBus->dispatch(new RecordTaskEventMessage(
            taskId: $event->taskId,
            teamId: $event->teamId ?? '',
            fromStatus: $event->fromStatus,
            toStatus: $event->toStatus,
            occurredAt: new \DateTimeImmutable(),
        ));

        $this->dispatchAction(
            taskId: $event->taskId,
            action: 'status_changed',
            actorId: '',
            metadata: json_encode(['from' => $event->fromStatus, 'to' => $event->toStatus], JSON_THROW_ON_ERROR),
        );
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskCreated(TaskCreated $event): void
    {
        $this->dispatchAction(
            taskId: $event->id->value(),
            action: 'created',
            actorId: $event->createdBy,
            metadata: json_encode(['title' => $event->title->value()], JSON_THROW_ON_ERROR),
        );
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskUpdated(TaskUpdated $event): void
    {
        $this->dispatchAction(
            taskId: $event->taskId,
            action: 'updated',
            actorId: '',
            metadata: '{}',
        );
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskDeleted(TaskDeleted $event): void
    {
        $this->dispatchAction(
            taskId: $event->taskId,
            action: 'deleted',
            actorId: '',
            metadata: '{}',
        );
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskAssigneeAdded(TaskAssigneeAdded $event): void
    {
        $this->dispatchAction(
            taskId: $event->taskId->value(),
            action: 'assignee_added',
            actorId: '',
            metadata: json_encode(['user_id' => $event->userId], JSON_THROW_ON_ERROR),
        );
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskAssigneeRemoved(TaskAssigneeRemoved $event): void
    {
        $this->dispatchAction(
            taskId: $event->taskId->value(),
            action: 'assignee_removed',
            actorId: '',
            metadata: json_encode(['user_id' => $event->userId], JSON_THROW_ON_ERROR),
        );
    }

    // TODO: subscribe to TaskClosed and TaskReopened and record the corresponding
    //       analytics actions — the task lifecycle is incomplete without them.

    private function dispatchAction(string $taskId, string $action, string $actorId, string $metadata): void
    {
        $this->defaultBus->dispatch(new RecordTaskActionMessage(
            taskId: $taskId,
            action: $action,
            actorId: $actorId,
            metadata: $metadata,
            occurredAt: new \DateTimeImmutable(),
        ));
    }
}
