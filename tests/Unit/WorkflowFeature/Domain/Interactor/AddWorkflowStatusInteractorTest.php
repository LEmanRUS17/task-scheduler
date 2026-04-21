<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowStatusInteractor;
use App\WorkflowFeature\Domain\Port\ClockInterface;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;

final class AddWorkflowStatusInteractorTest extends TestCase
{
    private ClockInterface $clock;
    private WorkflowId $workflowId;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));

        $this->workflowId = WorkflowId::fromString('550e8400-e29b-4d4d-a716-446655440000');
    }

    private function buildInteractor(
        WorkflowRepositoryInterface $workflows,
        WorkflowStatusRepositoryInterface $statuses,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): AddWorkflowStatusInteractor {
        return new AddWorkflowStatusInteractor(
            $workflows,
            $statuses,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    private function makeWorkflow(): Workflow
    {
        return Workflow::create(
            $this->workflowId,
            WorkflowTitle::fromString('Test'),
            'user-1',
            new \DateTimeImmutable(),
        );
    }

    private function workflowsWithFound(): WorkflowRepositoryInterface
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($this->makeWorkflow());

        return $workflows;
    }

    public function testAddSavesStatus(): void
    {
        $statuses = $this->createMock(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByLabel')->willReturn(null);
        $statuses->method('hasInitial')->willReturn(false);
        $statuses->expects($this->once())->method('save');

        $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->add($this->workflowId, StatusLabel::fromString('open'), true);
    }

    public function testAddDispatchesEvent(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByLabel')->willReturn(null);
        $statuses->method('hasInitial')->willReturn(false);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $this->buildInteractor($this->workflowsWithFound(), $statuses, $dispatcher)
            ->add($this->workflowId, StatusLabel::fromString('open'), true);
    }

    public function testAddReturnsStatus(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByLabel')->willReturn(null);
        $statuses->method('hasInitial')->willReturn(false);

        $status = $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->add($this->workflowId, StatusLabel::fromString('open'), true);

        $this->assertInstanceOf(WorkflowStatus::class, $status);
        $this->assertSame('open', $status->label()->value());
        $this->assertTrue($status->isInitial());
    }

    public function testAddThrowsWhenWorkflowNotFound(): void
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->buildInteractor($workflows, $this->createStub(WorkflowStatusRepositoryInterface::class))
            ->add($this->workflowId, StatusLabel::fromString('open'), false);
    }

    public function testAddThrowsWhenLabelAlreadyExists(): void
    {
        $existingStatus = WorkflowStatus::add(
            WorkflowStatusId::fromString('550e8400-e29b-4d4d-a716-446655440001'),
            $this->workflowId,
            StatusLabel::fromString('open'),
            false,
            new \DateTimeImmutable(),
        );

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByLabel')->willReturn($existingStatus);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/already exists/');

        $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->add($this->workflowId, StatusLabel::fromString('open'), false);
    }

    public function testAddThrowsWhenInitialAlreadyExists(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByLabel')->willReturn(null);
        $statuses->method('hasInitial')->willReturn(true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/initial status/');

        $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->add($this->workflowId, StatusLabel::fromString('open'), true);
    }
}
