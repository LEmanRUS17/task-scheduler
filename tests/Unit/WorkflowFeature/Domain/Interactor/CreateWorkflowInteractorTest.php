<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Interactor\CreateWorkflowInteractor;
use App\WorkflowFeature\Domain\Port\ClockInterface;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;

final class CreateWorkflowInteractorTest extends TestCase
{
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));
    }

    private function buildInteractor(
        WorkflowRepositoryInterface $workflows,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): CreateWorkflowInteractor {
        return new CreateWorkflowInteractor(
            $workflows,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    public function testCreateSavesWorkflow(): void
    {
        $workflows = $this->createMock(WorkflowRepositoryInterface::class);
        $workflows->expects($this->once())->method('save');

        $this->buildInteractor($workflows)->create(WorkflowTitle::fromString('My Workflow'), 'user-1');
    }

    public function testCreateDispatchesEvent(): void
    {
        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class), $dispatcher)
            ->create(WorkflowTitle::fromString('My Workflow'), 'user-1');
    }

    public function testCreateReturnsWorkflow(): void
    {
        $workflow = $this->buildInteractor($this->createStub(WorkflowRepositoryInterface::class))
            ->create(WorkflowTitle::fromString('My Workflow'), 'user-1');

        $this->assertInstanceOf(Workflow::class, $workflow);
        $this->assertSame('My Workflow', $workflow->title()->value());
        $this->assertSame('user-1', $workflow->createdBy());
    }
}
