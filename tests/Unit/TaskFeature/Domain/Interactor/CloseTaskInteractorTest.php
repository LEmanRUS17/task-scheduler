<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Event\TaskClosed;
use App\TaskFeature\Domain\Interactor\CloseTaskInteractor;
use App\TaskFeature\Domain\Port\ClockInterface;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use PHPUnit\Framework\TestCase;

final class CloseTaskInteractorTest extends TestCase
{
    private TaskId $taskId;
    private Task $task;
    private ClockInterface $clock;
    private \DateTimeImmutable $now;

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

        $this->now = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn($this->now);
    }

    private function buildInteractor(
        TaskRepositoryInterface $tasks,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): CloseTaskInteractor {
        return new CloseTaskInteractor(
            $tasks,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testCloseMarksTaskClosed(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $result = $this->buildInteractor($tasks)->close($this->taskId->value());

        $this->assertTrue($result->isClosed());
        $this->assertSame($this->now, $result->closedAt());
    }

    public function testCloseSavesTask(): void
    {
        $tasks = $this->createMock(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);
        $tasks->expects($this->once())->method('save');

        $this->buildInteractor($tasks)->close($this->taskId->value());
    }

    public function testCloseDispatchesTaskClosedEvent(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(TaskClosed::class));

        $this->buildInteractor($tasks, $dispatcher)->close($this->taskId->value());
    }

    public function testCloseThrowsWhenTaskNotFound(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($tasks)->close($this->taskId->value());
    }

    public function testCloseThrowsWhenAlreadyClosed(): void
    {
        $this->task->close(new \DateTimeImmutable());

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($tasks)->close($this->taskId->value());
    }
}
