<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Interactor\ApplyTaskTransitionInteractor;
use App\TaskFeature\Domain\Port\ClockInterface;
use App\TaskFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TaskFeature\Domain\Port\TaskWorkflowInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;
use PHPUnit\Framework\TestCase;

final class ApplyTaskTransitionInteractorTest extends TestCase
{
    private TaskId $taskId;
    private Task $task;
    private WorkflowTransition $transition;
    private WorkflowTransitionId $transitionId;
    private ClockInterface $clock;

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

        $this->transitionId = WorkflowTransitionId::generate();
        $this->transition = WorkflowTransition::add(
            $this->transitionId,
            WorkflowId::generate(),
            TransitionName::fromString('to_in_progress'),
            WorkflowStatusId::generate(),
            WorkflowStatusId::generate(),
            new \DateTimeImmutable(),
        );

        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));
    }

    private function buildInteractor(
        TaskRepositoryInterface $tasks,
        TaskWorkflowInterface $workflow,
        WorkflowTransitionRepositoryInterface $transitions,
        ?WorkflowStatusRepositoryInterface $statuses = null,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): ApplyTaskTransitionInteractor {
        return new ApplyTaskTransitionInteractor(
            $tasks,
            $workflow,
            $transitions,
            $statuses ?? $this->createStub(WorkflowStatusRepositoryInterface::class),
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    private function makeTaskWithWorkflowId(string $workflowId): Task
    {
        return Task::create(
            TaskId::generate(),
            TaskTitle::fromString('Task'),
            TaskPriority::NORMAL,
            $workflowId,
            null,
            'user-1',
            new \DateTimeImmutable(),
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

    public function testApplyCallsWorkflowTransition(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->transition);

        $workflow = $this->createMock(TaskWorkflowInterface::class);
        $workflow->expects($this->once())
            ->method('applyTransition')
            ->with($this->task, 'to_in_progress');

        $this->buildInteractor($tasks, $workflow, $transitions)
            ->apply($this->taskId->value(), $this->transitionId->value());
    }

    public function testApplySavesTask(): void
    {
        $tasks = $this->createMock(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);
        $tasks->expects($this->once())->method('save');

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->transition);

        $this->buildInteractor($tasks, $this->createStub(TaskWorkflowInterface::class), $transitions)
            ->apply($this->taskId->value(), $this->transitionId->value());
    }

    public function testApplyReturnsTask(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->transition);

        $result = $this->buildInteractor($tasks, $this->createStub(TaskWorkflowInterface::class), $transitions)
            ->apply($this->taskId->value(), $this->transitionId->value());

        $this->assertInstanceOf(Task::class, $result);
        $this->assertSame($this->taskId->value(), $result->id()->value());
    }

    public function testApplyThrowsWhenTaskNotFound(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor(
            $tasks,
            $this->createStub(TaskWorkflowInterface::class),
            $this->createStub(WorkflowTransitionRepositoryInterface::class),
        )->apply($this->taskId->value(), $this->transitionId->value());
    }

    public function testApplyThrowsWhenTransitionNotFound(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildInteractor($tasks, $this->createStub(TaskWorkflowInterface::class), $transitions)
            ->apply($this->taskId->value(), $this->transitionId->value());
    }

    public function testApplyThrowsWhenTaskIsClosed(): void
    {
        $this->task->close(new \DateTimeImmutable());

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/closed/');

        $this->buildInteractor(
            $tasks,
            $this->createStub(TaskWorkflowInterface::class),
            $this->createStub(WorkflowTransitionRepositoryInterface::class),
        )->apply($this->taskId->value(), $this->transitionId->value());
    }

    public function testApplyClosesTaskWhenTargetStatusIsFinal(): void
    {
        $task = $this->makeTaskWithWorkflowId(WorkflowId::generate()->value());
        $taskId = $task->id()->value();

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($task);

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->transition);

        $workflow = $this->createStub(TaskWorkflowInterface::class);
        $workflow->method('applyTransition')
            ->willReturnCallback(fn(Task $task) => $task->setWorkflowStatus('final-status-id'));

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus(true));

        $result = $this->buildInteractor($tasks, $workflow, $transitions, $statuses)
            ->apply($taskId, $this->transitionId->value());

        $this->assertTrue($result->isClosed());
    }

    public function testApplyDispatchesTaskClosedEventWhenTargetStatusIsFinal(): void
    {
        $task = $this->makeTaskWithWorkflowId(WorkflowId::generate()->value());
        $taskId = $task->id()->value();

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($task);

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->transition);

        $workflow = $this->createStub(TaskWorkflowInterface::class);
        $workflow->method('applyTransition')
            ->willReturnCallback(fn(Task $task) => $task->setWorkflowStatus('final-status-id'));

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus(true));

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch');

        $this->buildInteractor($tasks, $workflow, $transitions, $statuses, $dispatcher)
            ->apply($taskId, $this->transitionId->value());
    }

    public function testApplyDoesNotCloseTaskWhenTargetStatusIsNotFinal(): void
    {
        $task = $this->makeTaskWithWorkflowId(WorkflowId::generate()->value());
        $taskId = $task->id()->value();

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($task);

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->transition);

        $workflow = $this->createStub(TaskWorkflowInterface::class);
        $workflow->method('applyTransition')
            ->willReturnCallback(fn(Task $task) => $task->setWorkflowStatus('non-final-status-id'));

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus(false));

        $result = $this->buildInteractor($tasks, $workflow, $transitions, $statuses)
            ->apply($taskId, $this->transitionId->value());

        $this->assertFalse($result->isClosed());
    }

    public function testApplyDoesNotCloseTaskWhenStatusIsUnknown(): void
    {
        $task = $this->makeTaskWithWorkflowId(WorkflowId::generate()->value());
        $taskId = $task->id()->value();

        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($task);

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->transition);

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn(null);

        $result = $this->buildInteractor(
            $tasks,
            $this->createStub(TaskWorkflowInterface::class),
            $transitions,
            $statuses,
        )->apply($taskId, $this->transitionId->value());

        $this->assertFalse($result->isClosed());
    }

    public function testApplyDoesNotCloseTaskWhenWorkflowIdIsNotAValidUuid(): void
    {
        $tasks = $this->createStub(TaskRepositoryInterface::class);
        $tasks->method('findById')->willReturn($this->task);

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->transition);

        $result = $this->buildInteractor($tasks, $this->createStub(TaskWorkflowInterface::class), $transitions)
            ->apply($this->taskId->value(), $this->transitionId->value());

        $this->assertFalse($result->isClosed());
    }
}
