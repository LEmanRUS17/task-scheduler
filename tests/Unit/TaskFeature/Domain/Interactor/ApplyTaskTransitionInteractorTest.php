<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\Interactor;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Interactor\ApplyTaskTransitionInteractor;
use App\TaskFeature\Domain\Port\TaskWorkflowInterface;
use App\TaskFeature\Domain\Repository\TaskRepositoryInterface;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;
use PHPUnit\Framework\TestCase;

final class ApplyTaskTransitionInteractorTest extends TestCase
{
    private TaskId $taskId;
    private Task $task;
    private WorkflowTransition $transition;
    private WorkflowTransitionId $transitionId;

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
            StatusLabel::fromString('todo'),
            StatusLabel::fromString('in_progress'),
            new \DateTimeImmutable(),
        );
    }

    private function buildInteractor(
        TaskRepositoryInterface $tasks,
        TaskWorkflowInterface $workflow,
        WorkflowTransitionRepositoryInterface $transitions,
    ): ApplyTaskTransitionInteractor {
        return new ApplyTaskTransitionInteractor($tasks, $workflow, $transitions);
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
}
