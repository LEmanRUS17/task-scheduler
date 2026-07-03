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
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
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
        ?WorkflowStatusRepositoryInterface $statuses = null,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): ReopenTaskInteractor {
        return new ReopenTaskInteractor(
            $tasks,
            $statuses ?? $this->createStub(WorkflowStatusRepositoryInterface::class),
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
        );
    }

    private function makeStatus(bool $isFinal): WorkflowStatus
    {
        return WorkflowStatus::add(
            WorkflowStatusId::generate(),
            WorkflowId::generate(),
            StatusLabel::fromString('some-status'),
            false,
            new \DateTimeImmutable(),
            $isFinal,
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

        $this->buildInteractor($tasks, dispatcher: $dispatcher)->reopen($this->taskId->value());
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

    public function testReopenThrowsWhenTaskStatusIsFinal(): void
    {
        $task = Task::create(
            TaskId::generate(),
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            WorkflowId::generate()->value(),
            null,
            'user-1',
            new \DateTimeImmutable(),
        );
        $task->close(new \DateTimeImmutable());

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($task);

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus(true));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/final status/');

        $this->buildInteractor($tasks, $statuses)->reopen($task->id()->value());
    }

    public function testReopenDoesNotSaveTaskWhenStatusIsFinal(): void
    {
        $task = Task::create(
            TaskId::generate(),
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            WorkflowId::generate()->value(),
            null,
            'user-1',
            new \DateTimeImmutable(),
        );
        $task->close(new \DateTimeImmutable());

        $tasks = $this->createMock(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($task);
        $tasks->expects($this->never())->method('save');

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus(true));

        try {
            $this->buildInteractor($tasks, $statuses)->reopen($task->id()->value());
        } catch (\DomainException) {
        }
    }

    public function testReopenSucceedsWhenTaskStatusIsNotFinal(): void
    {
        $task = Task::create(
            TaskId::generate(),
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            WorkflowId::generate()->value(),
            null,
            'user-1',
            new \DateTimeImmutable(),
        );
        $task->close(new \DateTimeImmutable());

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($task);

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus(false));

        $result = $this->buildInteractor($tasks, $statuses)->reopen($task->id()->value());

        $this->assertFalse($result->isClosed());
    }
}
