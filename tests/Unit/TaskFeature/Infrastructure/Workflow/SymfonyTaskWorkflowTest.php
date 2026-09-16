<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Infrastructure\Workflow;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use App\TaskFeature\Infrastructure\Workflow\SymfonyTaskWorkflow;
use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use App\WorkflowFeature\Infrastructure\Workflow\DynamicWorkflowLoader;
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
        ?DynamicWorkflowLoader $workflowLoader = null,
    ): SymfonyTaskWorkflow {
        return new SymfonyTaskWorkflow(
            $registry,
            $workflows,
            $this->createStub(WorkflowStatusRepositoryInterface::class),
            $workflowLoader ?? $this->makeNoopWorkflowLoader(),
        );
    }

    /**
     * A loader whose ensureLoaded() finds nothing to register — used by tests that
     * pre-populate (or stub) the Registry themselves and don't care about loading.
     */
    private function makeNoopWorkflowLoader(): DynamicWorkflowLoader
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findAll')->willReturn([]);

        return new DynamicWorkflowLoader(
            new Registry(),
            $workflows,
            $this->createStub(WorkflowStatusRepositoryInterface::class),
            $this->createStub(WorkflowTransitionRepositoryInterface::class),
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

    /**
     * Regression test for a bug where DynamicWorkflowLoader only populated the
     * Registry as a kernel.request listener, which never fires in a console command
     * (e.g. the messenger:consume worker handling TaskStatusChangedMessage) — every
     * such lookup failed with "Unable to find a workflow for class ...". Uses a
     * fresh, never-manually-populated Registry shared with a real DynamicWorkflowLoader,
     * exactly like production DI wiring, so this fails again if the ensureLoaded()
     * call is ever removed from SymfonyTaskWorkflow.
     */
    public function testGetEnabledTransitionsWorksWithAFreshRegistryPopulatedOnFirstUse(): void
    {
        $workflowId = WorkflowId::fromString($this->task->getWorkflowDefinitionTitle());
        $status = WorkflowStatus::add(
            WorkflowStatusId::generate(),
            $workflowId,
            StatusLabel::fromString('open'),
            true,
            new \DateTimeImmutable(),
        );
        $this->task->setWorkflowStatus($status->id()->value());

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findAll')->willReturn([$this->workflowEntity]);
        $workflows->method('findById')->willReturn($this->workflowEntity);

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByWorkflowId')->willReturn([$status]);

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findByWorkflowId')->willReturn([]);

        $sharedRegistry = new Registry();
        $workflowLoader = new DynamicWorkflowLoader($sharedRegistry, $workflows, $statuses, $transitions);

        $result = $this->buildSut($sharedRegistry, $workflows, $workflowLoader)
            ->getEnabledTransitions($this->task);

        $this->assertSame([], $result);
    }
}
