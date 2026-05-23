<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Messenger\EventSubscriber;

use App\SearchFeature\Infrastructure\Messenger\EventSubscriber\TaskSearchIndexSubscriber;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexTaskMessage;
use App\TaskFeature\Domain\Event\TaskCreated;
use App\TaskFeature\Domain\Event\TaskStatusChanged;
use App\TaskFeature\Domain\Event\TaskUpdated;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class TaskSearchIndexSubscriberTest extends TestCase
{
    private function makeBus(?IndexTaskMessage &$captured = null): MessageBusInterface
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (IndexTaskMessage $message) use (&$captured) {
                $captured = $message;
                return new Envelope($message);
            });

        return $bus;
    }

    public function testOnTaskCreatedDispatchesIndexTaskMessageWithTaskId(): void
    {
        $taskId = TaskId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $event = new TaskCreated($taskId, TaskTitle::fromString('Fix bug'), 'user-1');

        $captured = null;
        (new TaskSearchIndexSubscriber($this->makeBus($captured)))->onTaskCreated($event);

        $this->assertInstanceOf(IndexTaskMessage::class, $captured);
        $this->assertSame($taskId->value(), $captured->taskId);
    }

    public function testOnTaskUpdatedDispatchesIndexTaskMessageWithTaskId(): void
    {
        $event = new TaskUpdated('task-uuid');

        $captured = null;
        (new TaskSearchIndexSubscriber($this->makeBus($captured)))->onTaskUpdated($event);

        $this->assertInstanceOf(IndexTaskMessage::class, $captured);
        $this->assertSame('task-uuid', $captured->taskId);
    }

    public function testOnTaskStatusChangedDispatchesIndexTaskMessageWithTaskId(): void
    {
        $event = new TaskStatusChanged('task-uuid', 'tr-uuid', 'todo', 'in_progress', 'default', null);

        $captured = null;
        (new TaskSearchIndexSubscriber($this->makeBus($captured)))->onTaskStatusChanged($event);

        $this->assertInstanceOf(IndexTaskMessage::class, $captured);
        $this->assertSame('task-uuid', $captured->taskId);
    }
}
