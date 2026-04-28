<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Interactor\UpdateTaskInteractor;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use PHPUnit\Framework\TestCase;

final class UpdateTaskInteractorTest extends TestCase
{
    private TaskId $taskId;
    private Task $task;

    protected function setUp(): void
    {
        $this->taskId = TaskId::generate();
        $this->task = Task::create(
            $this->taskId,
            TaskTitle::fromString('Original'),
            TaskPriority::LOW,
            'default',
            null,
            'user-1',
            new \DateTimeImmutable(),
        );
    }

    public function testUpdateReturnsSavedTask(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $result = (new UpdateTaskInteractor($tasks))->update(
            $this->taskId->value(),
            TaskTitle::fromString('Updated'),
            TaskPriority::HIGH,
            null,
            null,
            null,
        );

        $this->assertSame('Updated', $result->title()->value());
        $this->assertSame(TaskPriority::HIGH, $result->priority());
    }

    public function testUpdateSavesTask(): void
    {
        $tasks = $this->createMock(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);
        $tasks->expects($this->once())->method('save');

        (new UpdateTaskInteractor($tasks))->update(
            $this->taskId->value(),
            null,
            null,
            null,
            null,
            null,
        );
    }

    public function testUpdateThrowsWhenTaskNotFound(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        (new UpdateTaskInteractor($tasks))->update(
            $this->taskId->value(),
            null,
            null,
            null,
            null,
            null,
        );
    }

    public function testUpdateScheduledDates(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $start = new \DateTimeImmutable('2024-06-01');
        $end = new \DateTimeImmutable('2024-06-30');

        $result = (new UpdateTaskInteractor($tasks))->update(
            $this->taskId->value(),
            null,
            null,
            $start,
            $end,
            180,
        );

        $this->assertSame($start, $result->scheduledStart());
        $this->assertSame($end, $result->scheduledEnd());
        $this->assertSame(180, $result->estimatedTime());
    }
}
