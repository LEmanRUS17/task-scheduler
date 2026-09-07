<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Entity\TaskAssignee;
use App\TaskFeature\Domain\Event\TaskAssigneeAdded;
use App\TaskFeature\Domain\Event\TaskAssigneeRemoved;
use App\TaskFeature\Domain\Interactor\SetTaskAssigneesInteractor;
use App\TaskFeature\Domain\Port\ClockInterface;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Repository\TaskAssigneeRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use PHPUnit\Framework\TestCase;

final class SetTaskAssigneesInteractorTest extends TestCase
{
    private ClockInterface $clock;
    private TaskId $taskId;
    private Task $task;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));

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
    }

    private function buildInteractor(
        TaskRepositoryInterface $tasks,
        TaskAssigneeRepositoryInterface $assignees,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): SetTaskAssigneesInteractor {
        return new SetTaskAssigneesInteractor(
            $tasks,
            $assignees,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testSetAddsNewAssignees(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $assignees = $this->createMock(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskId')->willReturn([]);
        $assignees->expects($this->once())->method('save');

        $this->buildInteractor($tasks, $assignees)->set($this->taskId, ['user-new']);
    }

    public function testSetDispatchesAddedEventForNewAssignees(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskId')->willReturn([]);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch')
            ->with($this->isInstanceOf(TaskAssigneeAdded::class));

        $this->buildInteractor($tasks, $assignees, $dispatcher)->set($this->taskId, ['user-new']);
    }

    public function testSetRemovesAssigneesNoLongerPresent(): void
    {
        $existing = TaskAssignee::assign($this->taskId, 'user-old', new \DateTimeImmutable());

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $assignees = $this->createMock(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskId')->willReturn([$existing]);
        $assignees->expects($this->once())->method('delete')->with($existing);

        $this->buildInteractor($tasks, $assignees)->set($this->taskId, []);
    }

    public function testSetDispatchesRemovedEventForDroppedAssignees(): void
    {
        $existing = TaskAssignee::assign($this->taskId, 'user-old', new \DateTimeImmutable());

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskId')->willReturn([$existing]);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch')
            ->with($this->isInstanceOf(TaskAssigneeRemoved::class));

        $this->buildInteractor($tasks, $assignees, $dispatcher)->set($this->taskId, []);
    }

    public function testSetKeepsAssigneesPresentInBothSets(): void
    {
        $existing = TaskAssignee::assign($this->taskId, 'user-kept', new \DateTimeImmutable());

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $assignees = $this->createMock(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskId')->willReturn([$existing]);
        $assignees->expects($this->never())->method('save');
        $assignees->expects($this->never())->method('delete');

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $this->buildInteractor($tasks, $assignees, $dispatcher)->set($this->taskId, ['user-kept']);
    }

    public function testSetIgnoresDuplicateUserIds(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $assignees = $this->createMock(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskId')->willReturn([]);
        $assignees->expects($this->once())->method('save');

        $this->buildInteractor($tasks, $assignees)->set($this->taskId, ['user-new', 'user-new']);
    }

    public function testSetThrowsWhenTaskNotFound(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($tasks, $this->createStub(TaskAssigneeRepositoryInterface::class))
            ->set($this->taskId, ['user-new']);
    }
}
