<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Entity\TaskAssignee;
use App\TaskFeature\Domain\Event\TaskAssigneeAdded;
use App\TaskFeature\Domain\Interactor\AddTaskAssigneeInteractor;
use App\TaskFeature\Domain\Port\ClockInterface;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Port\TeamMembershipInterface;
use App\TaskFeature\Domain\Repository\TaskAssigneeRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use PHPUnit\Framework\TestCase;

final class AddTaskAssigneeInteractorTest extends TestCase
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
        TeamMembershipInterface $membership,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): AddTaskAssigneeInteractor {
        return new AddTaskAssigneeInteractor(
            $tasks,
            $assignees,
            $membership,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testAddSavesAssignee(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $membership = $this->createStub(TeamMembershipInterface::class);
        $membership->method('isMember')->willReturn(true);

        $assignees = $this->createMock(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskAndUser')->willReturn(null);
        $assignees->expects($this->once())->method('save');

        $this->buildInteractor($tasks, $assignees, $membership)->add($this->taskId, 'user-new');
    }

    public function testAddReturnsAssignee(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $membership = $this->createStub(TeamMembershipInterface::class);
        $membership->method('isMember')->willReturn(true);

        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskAndUser')->willReturn(null);

        $result = $this->buildInteractor($tasks, $assignees, $membership)->add($this->taskId, 'user-new');

        $this->assertInstanceOf(TaskAssignee::class, $result);
        $this->assertSame($this->taskId->value(), $result->taskId()->value());
        $this->assertSame('user-new', $result->userId());
    }

    public function testAddDispatchesEvent(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $membership = $this->createStub(TeamMembershipInterface::class);
        $membership->method('isMember')->willReturn(true);

        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskAndUser')->willReturn(null);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch')
            ->with($this->isInstanceOf(TaskAssigneeAdded::class));

        $this->buildInteractor($tasks, $assignees, $membership, $dispatcher)->add($this->taskId, 'user-new');
    }

    public function testAddThrowsWhenTaskNotFound(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor(
            $tasks,
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $this->createStub(TeamMembershipInterface::class),
        )->add($this->taskId, 'user-new');
    }

    public function testAddThrowsWhenTaskHasNoTeam(): void
    {
        $taskWithoutTeam = Task::create(
            $this->taskId,
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            'default',
            null,
            'user-creator',
            new \DateTimeImmutable(),
        );

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($taskWithoutTeam);

        $this->expectException(\DomainException::class);

        $this->buildInteractor(
            $tasks,
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $this->createStub(TeamMembershipInterface::class),
        )->add($this->taskId, 'user-new');
    }

    public function testAddThrowsWhenUserNotTeamMember(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $membership = $this->createStub(TeamMembershipInterface::class);
        $membership->method('isMember')->willReturn(false);

        $this->expectException(\DomainException::class);

        $this->buildInteractor(
            $tasks,
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $membership,
        )->add($this->taskId, 'user-new');
    }

    public function testAddThrowsWhenAlreadyAssigned(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $membership = $this->createStub(TeamMembershipInterface::class);
        $membership->method('isMember')->willReturn(true);

        $existing = TaskAssignee::assign($this->taskId, 'user-new', new \DateTimeImmutable());
        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);
        $assignees->method('findByTaskAndUser')->willReturn($existing);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($tasks, $assignees, $membership)->add($this->taskId, 'user-new');
    }
}
