<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Event\TaskReopened;
use App\TaskFeature\Domain\Interactor\ReopenTaskInteractor;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use PHPUnit\Framework\TestCase;

final class ReopenTaskInteractorTest extends TestCase
{
    private TaskId $taskId;
    private Task $task;

    protected function setUp(): void
    {
        $this->taskId = TaskId::generate();
        $this->task = Task::create(
            $this->taskId,
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            'default',
            null,
            'user-1',
            new \DateTimeImmutable(),
        );
        $this->task->close(new \DateTimeImmutable());
    }

    private function buildInteractor(
        TaskRepositoryInterface $tasks,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): ReopenTaskInteractor {
        return new ReopenTaskInteractor(
            $tasks,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
        );
    }

    public function testReopenClearsClosedState(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $result = $this->buildInteractor($tasks)->reopen($this->taskId->value());

        $this->assertFalse($result->isClosed());
    }

    public function testReopenSavesTask(): void
    {
        $tasks = $this->createMock(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);
        $tasks->expects($this->once())->method('save');

        $this->buildInteractor($tasks)->reopen($this->taskId->value());
    }

    public function testReopenDispatchesTaskReopenedEvent(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(TaskReopened::class));

        $this->buildInteractor($tasks, $dispatcher)->reopen($this->taskId->value());
    }

    public function testReopenThrowsWhenTaskNotFound(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($tasks)->reopen($this->taskId->value());
    }

    public function testReopenThrowsWhenTaskNotClosed(): void
    {
        $openTask = Task::create(
            TaskId::generate(),
            TaskTitle::fromString('Open Task'),
            TaskPriority::NORMAL,
            'default',
            null,
            'user-1',
            new \DateTimeImmutable(),
        );

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($openTask);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($tasks)->reopen($this->taskId->value());
    }
}
