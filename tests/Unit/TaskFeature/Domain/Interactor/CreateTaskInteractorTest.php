<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Interactor\CreateTaskInteractor;
use App\TaskFeature\Domain\Port\ClockInterface;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Port\TaskWorkflowInterface;
use App\TaskFeature\Domain\Repository\TaskAssigneeRepositoryInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use PHPUnit\Framework\TestCase;

final class CreateTaskInteractorTest extends TestCase
{
    private ClockInterface $clock;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn($this->now);
    }

    private function buildInteractor(
        TaskRepositoryInterface $tasks,
        TaskAssigneeRepositoryInterface $assignees,
        ?DomainEventDispatcherInterface $dispatcher = null,
        ?TaskWorkflowInterface $workflow = null,
    ): CreateTaskInteractor {
        return new CreateTaskInteractor(
            $tasks,
            $assignees,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
            $workflow ?? $this->createStub(TaskWorkflowInterface::class),
        );
    }

    public function testCreateReturnsTask(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $assignees = $this->createStub(TaskAssigneeRepositoryInterface::class);

        $task = $this->buildInteractor($tasks, $assignees)->create(
            TaskTitle::fromString('My Task'),
            TaskPriority::NORMAL,
            'default',
            null,
            'user-1',
        );

        $this->assertInstanceOf(Task::class, $task);
        $this->assertSame('My Task', $task->title()->value());
        $this->assertSame(TaskPriority::NORMAL, $task->priority());
        $this->assertSame('user-1', $task->createdBy());
    }

    public function testCreateSavesTask(): void
    {
        $tasks = $this->createMock(TaskRepositoryInterface::class);
        $tasks->expects($this->once())->method('save');

        $this->buildInteractor($tasks, $this->createStub(TaskAssigneeRepositoryInterface::class))->create(
            TaskTitle::fromString('Task'),
            TaskPriority::LOW,
            'default',
            null,
            'user-1',
        );
    }

    public function testCreateInitializesWorkflow(): void
    {
        $workflow = $this->createMock(TaskWorkflowInterface::class);
        $workflow->expects($this->once())->method('initialize');

        $this->buildInteractor(
            $this->createStub(TaskRepositoryInterface::class),
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            workflow: $workflow,
        )->create(
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            'default',
            null,
            'user-1',
        );
    }

    public function testCreateDispatchesTaskCreatedEvent(): void
    {
        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $this->buildInteractor(
            $this->createStub(TaskRepositoryInterface::class),
            $this->createStub(TaskAssigneeRepositoryInterface::class),
            $dispatcher,
        )->create(
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            'default',
            null,
            'user-1',
        );
    }

    public function testCreateWithNoTeamAssignsCreatorOnly(): void
    {
        $assignees = $this->createMock(TaskAssigneeRepositoryInterface::class);
        $assignees->expects($this->once())->method('save');

        $this->buildInteractor($this->createStub(TaskRepositoryInterface::class), $assignees)->create(
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            'default',
            null,
            'user-creator',
            ['user-other'],
        );
    }

    public function testCreateWithTeamAndAssigneesUsesProvidedAssignees(): void
    {
        $assignees = $this->createMock(TaskAssigneeRepositoryInterface::class);
        $assignees->expects($this->exactly(2))->method('save');

        $this->buildInteractor($this->createStub(TaskRepositoryInterface::class), $assignees)->create(
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            'default',
            'team-1',
            'user-creator',
            ['user-a', 'user-b'],
        );
    }

    public function testCreateWithTeamButNoAssigneesAssignsCreator(): void
    {
        $assignees = $this->createMock(TaskAssigneeRepositoryInterface::class);
        $assignees->expects($this->once())->method('save');

        $this->buildInteractor($this->createStub(TaskRepositoryInterface::class), $assignees)->create(
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            'default',
            'team-1',
            'user-creator',
            [],
        );
    }
}
