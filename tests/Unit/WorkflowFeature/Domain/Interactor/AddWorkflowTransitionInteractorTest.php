<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Interactor\AddWorkflowTransitionInteractor;
use App\WorkflowFeature\Domain\Port\ClockInterface;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;

final class AddWorkflowTransitionInteractorTest extends TestCase
{
    private ClockInterface $clock;
    private WorkflowId $workflowId;
    private StatusLabel $from;
    private StatusLabel $to;
    private TransitionName $name;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));

        $this->workflowId = WorkflowId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $this->from = StatusLabel::fromString('open');
        $this->to = StatusLabel::fromString('closed');
        $this->name = TransitionName::fromString('close');
    }

    private function buildInteractor(
        WorkflowRepositoryInterface $workflows,
        WorkflowStatusRepositoryInterface $statuses,
        WorkflowTransitionRepositoryInterface $transitions,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): AddWorkflowTransitionInteractor {
        return new AddWorkflowTransitionInteractor(
            $workflows,
            $statuses,
            $transitions,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
        );
    }

    private function workflowsWithFound(): WorkflowRepositoryInterface
    {
        $workflow = Workflow::create(
            $this->workflowId,
            WorkflowTitle::fromString('Test'),
            'user-1',
            new \DateTimeImmutable(),
        );

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($workflow);

        return $workflows;
    }

    private function makeStatus(string $label): WorkflowStatus
    {
        return WorkflowStatus::add(
            WorkflowStatusId::generate(),
            $this->workflowId,
            StatusLabel::fromString($label),
            false,
            new \DateTimeImmutable(),
        );
    }

    private function statusesWithBothFound(): WorkflowStatusRepositoryInterface
    {
        $status = $this->makeStatus('open');

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByLabel')->willReturn($status);

        return $statuses;
    }

    private function transitionsWithNoConflict(): WorkflowTransitionRepositoryInterface
    {
        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('existsByName')->willReturn(false);

        return $transitions;
    }

    public function testAddSavesTransition(): void
    {
        $transitions = $this->createMock(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('existsByName')->willReturn(false);
        $transitions->expects($this->once())->method('save');

        $this->buildInteractor($this->workflowsWithFound(), $this->statusesWithBothFound(), $transitions)
            ->add($this->workflowId, $this->name, $this->from, $this->to);
    }

    public function testAddDispatchesEvent(): void
    {
        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $this->buildInteractor(
            $this->workflowsWithFound(),
            $this->statusesWithBothFound(),
            $this->transitionsWithNoConflict(),
            $dispatcher,
        )->add($this->workflowId, $this->name, $this->from, $this->to);
    }

    public function testAddReturnsTransition(): void
    {
        $transition = $this->buildInteractor(
            $this->workflowsWithFound(),
            $this->statusesWithBothFound(),
            $this->transitionsWithNoConflict(),
        )->add($this->workflowId, $this->name, $this->from, $this->to);

        $this->assertInstanceOf(WorkflowTransition::class, $transition);
        $this->assertSame('close', $transition->name()->value());
        $this->assertSame('open', $transition->fromStatusLabel()->value());
        $this->assertSame('closed', $transition->toStatusLabel()->value());
    }

    public function testAddThrowsWhenWorkflowNotFound(): void
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->buildInteractor(
            $workflows,
            $this->createStub(WorkflowStatusRepositoryInterface::class),
            $this->createStub(WorkflowTransitionRepositoryInterface::class),
        )->add($this->workflowId, $this->name, $this->from, $this->to);
    }

    public function testAddThrowsWhenFromStatusNotFound(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByLabel')->willReturnOnConsecutiveCalls(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->buildInteractor(
            $this->workflowsWithFound(),
            $statuses,
            $this->createStub(WorkflowTransitionRepositoryInterface::class),
        )->add($this->workflowId, $this->name, $this->from, $this->to);
    }

    public function testAddThrowsWhenToStatusNotFound(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByLabel')->willReturnOnConsecutiveCalls($this->makeStatus('open'), null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->buildInteractor(
            $this->workflowsWithFound(),
            $statuses,
            $this->createStub(WorkflowTransitionRepositoryInterface::class),
        )->add($this->workflowId, $this->name, $this->from, $this->to);
    }

    public function testAddThrowsWhenTransitionNameAlreadyExists(): void
    {
        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('existsByName')->willReturn(true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/already exists/');

        $this->buildInteractor($this->workflowsWithFound(), $this->statusesWithBothFound(), $transitions)
            ->add($this->workflowId, $this->name, $this->from, $this->to);
    }
}
