<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Infrastructure\Workflow;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use App\TaskFeature\Infrastructure\Workflow\SymfonyTaskWorkflow;
use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

final class SymfonyTaskWorkflowTest extends TestCase
{
    private Task $task;
    private Workflow $workflowEntity;

    protected function setUp(): void
    {
        $this->task = Task::create(
            TaskId::generate(),
            TaskTitle::fromString('Test task'),
            TaskPriority::NORMAL,
            WorkflowId::generate()->value(),
            null,
            'user-1',
            new \DateTimeImmutable(),
        );

        $this->workflowEntity = Workflow::create(
            WorkflowId::fromString($this->task->getWorkflowDefinitionTitle()),
            WorkflowTitle::fromString('default'),
            'user-1',
            new \DateTimeImmutable(),
        );
    }

    private function buildSut(
        Registry $registry,
        WorkflowRepositoryInterface $workflows,
    ): SymfonyTaskWorkflow {
        return new SymfonyTaskWorkflow(
            $registry,
            $workflows,
            $this->createStub(WorkflowStatusRepositoryInterface::class),
        );
    }

    public function testGetEnabledTransitionsReturnsTransitionNames(): void
    {
        $symfonyWorkflow = $this->createStub(WorkflowInterface::class);
        $symfonyWorkflow->method('getEnabledTransitions')->willReturn([
            new Transition('start', 'todo', 'in_progress'),
            new Transition('review', 'in_progress', 'review'),
        ]);

        $registry = $this->createStub(Registry::class);
        $registry->method('get')->willReturn($symfonyWorkflow);

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($this->workflowEntity);

        $result = $this->buildSut($registry, $workflows)->getEnabledTransitions($this->task);

        $this->assertSame(['start', 'review'], $result);
    }

    public function testGetEnabledTransitionsReturnsEmptyArrayWhenNoneAvailable(): void
    {
        $symfonyWorkflow = $this->createStub(WorkflowInterface::class);
        $symfonyWorkflow->method('getEnabledTransitions')->willReturn([]);

        $registry = $this->createStub(Registry::class);
        $registry->method('get')->willReturn($symfonyWorkflow);

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($this->workflowEntity);

        $result = $this->buildSut($registry, $workflows)->getEnabledTransitions($this->task);

        $this->assertSame([], $result);
    }

    public function testGetEnabledTransitionsThrowsWhenWorkflowNotFound(): void
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildSut($this->createStub(Registry::class), $workflows)
            ->getEnabledTransitions($this->task);
    }

    public function testGetEnabledTransitionsPassesCorrectTaskToSymfonyWorkflow(): void
    {
        $symfonyWorkflow = $this->createMock(WorkflowInterface::class);
        $symfonyWorkflow->expects($this->once())
            ->method('getEnabledTransitions')
            ->with($this->task)
            ->willReturn([]);

        $registry = $this->createStub(Registry::class);
        $registry->method('get')->willReturn($symfonyWorkflow);

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($this->workflowEntity);

        $this->buildSut($registry, $workflows)->getEnabledTransitions($this->task);
    }
}
