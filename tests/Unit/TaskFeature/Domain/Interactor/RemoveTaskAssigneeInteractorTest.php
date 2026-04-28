<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Entity\TaskAssignee;
use App\TaskFeature\Domain\Interactor\RemoveTaskAssigneeInteractor;
use App\TaskFeature\Domain\Repository\TaskAssigneeRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use PHPUnit\Framework\TestCase;

final class RemoveTaskAssigneeInteractorTest extends TestCase
{
    private TaskId $taskId;
    private Task $task;
    private TaskAssignee $assignee;

    protected function setUp(): void
    {
        $this->taskId = TaskId::generate();
        $this->task = Task::create(
            $this->taskId,
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            'default',
            'team-1',
            'user-creator',
            new \DateTimeImmutable(),
        );
        $this->assignee = TaskAssignee::assign($this->taskId, 'user-1', new \DateTimeImmutable());
    }

    public function testRemoveDeletesAssignee(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $assignees = $this->createMock(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskAndUser')->willReturn($this->assignee);
        $assignees->expects($this->once())->method('delete')->with($this->assignee);

        (new RemoveTaskAssigneeInteractor($tasks, $assignees))->remove($this->taskId, 'user-1');
    }

    public function testRemoveThrowsWhenTaskNotFound(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        (new RemoveTaskAssigneeInteractor($tasks, $this->createStub(TaskAssigneeRepositoryInterface::class)))
            ->remove($this->taskId, 'user-1');
    }

    public function testRemoveThrowsWhenUserNotAssigned(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskAndUser')->willReturn(null);

        $this->expectException(\DomainException::class);

        (new RemoveTaskAssigneeInteractor($tasks, $assignees))->remove($this->taskId, 'user-1');
    }
}
