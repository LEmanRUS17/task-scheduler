<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowTransitionInteractor;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;
use PHPUnit\Framework\TestCase;

final class UpdateWorkflowTransitionInteractorTest extends TestCase
{
    private WorkflowId $workflowId;
    private WorkflowTransitionId $transitionId;
    private WorkflowStatusId $from;
    private WorkflowStatusId $to;

    protected function setUp(): void
    {
        $this->workflowId = WorkflowId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $this->transitionId = WorkflowTransitionId::fromString('550e8400-e29b-4d4d-a716-446655440002');
        $this->from = WorkflowStatusId::fromString('550e8400-e29b-4d4d-a716-446655440010');
        $this->to = WorkflowStatusId::fromString('550e8400-e29b-4d4d-a716-446655440011');
    }

    private function buildInteractor(
        WorkflowRepositoryInterface $workflows,
        WorkflowStatusRepositoryInterface $statuses,
        WorkflowTransitionRepositoryInterface $transitions,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): UpdateWorkflowTransitionInteractor {
        return new UpdateWorkflowTransitionInteractor(
            $workflows,
            $statuses,
            $transitions,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
        );
    }

    private function workflowsWithFound(): WorkflowRepositoryInterface
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn(Workflow::create(
            $this->workflowId,
            WorkflowTitle::fromString('Test'),
            'user-1',
            new \DateTimeImmutable(),
        ));

        return $workflows;
    }

    private function statusesWithBothFound(): WorkflowStatusRepositoryInterface
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn(WorkflowStatus::add(
            WorkflowStatusId::fromString('550e8400-e29b-4d4d-a716-446655440001'),
            $this->workflowId,
            StatusLabel::fromString('open'),
            true,
            new \DateTimeImmutable(),
        ));

        return $statuses;
    }

    private function makeTransition(): WorkflowTransition
    {
        return WorkflowTransition::add(
            $this->transitionId,
            $this->workflowId,
            TransitionName::fromString('start'),
            $this->from,
            $this->to,
            new \DateTimeImmutable(),
        );
    }

    public function testUpdateSavesTransition(): void
    {
        $transitions = $this->createMock(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->makeTransition());
        $transitions->method('existsByName')->willReturn(false);
        $transitions->expects($this->once())->method('save');

        $result = $this->buildInteractor(
            $this->workflowsWithFound(),
            $this->statusesWithBothFound(),
            $transitions,
        )->update(
            $this->workflowId,
            $this->transitionId,
            TransitionName::fromString('reopen'),
            $this->from,
            $this->to,
        );

        $this->assertSame('reopen', $result->name()->value());
    }

    public function testUpdateAllowsKeepingSameName(): void
    {
        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->makeTransition());
        $transitions->method('existsByName')->willReturn(true);

        $result = $this->buildInteractor(
            $this->workflowsWithFound(),
            $this->statusesWithBothFound(),
            $transitions,
        )->update(
            $this->workflowId,
            $this->transitionId,
            TransitionName::fromString('start'),
            $this->from,
            $this->to,
        );

        $this->assertSame('start', $result->name()->value());
    }

    public function testUpdateThrowsWhenWorkflowNotFound(): void
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->buildInteractor(
            $workflows,
            $this->statusesWithBothFound(),
            $this->createStub(WorkflowTransitionRepositoryInterface::class),
        )->update(
            $this->workflowId,
            $this->transitionId,
            TransitionName::fromString('start'),
            $this->from,
            $this->to,
        );
    }

    public function testUpdateThrowsWhenTransitionNotFound(): void
    {
        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->buildInteractor(
            $this->workflowsWithFound(),
            $this->statusesWithBothFound(),
            $transitions,
        )->update(
            $this->workflowId,
            $this->transitionId,
            TransitionName::fromString('start'),
            $this->from,
            $this->to,
        );
    }

    public function testUpdateThrowsWhenTransitionBelongsToAnotherWorkflow(): void
    {
        $foreign = WorkflowTransition::add(
            $this->transitionId,
            WorkflowId::fromString('550e8400-e29b-4d4d-a716-4466554400ff'),
            TransitionName::fromString('start'),
            $this->from,
            $this->to,
            new \DateTimeImmutable(),
        );

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($foreign);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->buildInteractor(
            $this->workflowsWithFound(),
            $this->statusesWithBothFound(),
            $transitions,
        )->update(
            $this->workflowId,
            $this->transitionId,
            TransitionName::fromString('start'),
            $this->from,
            $this->to,
        );
    }

    public function testUpdateThrowsWhenFromStatusNotFound(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn(null);

        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->makeTransition());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found in this workflow/');

        $this->buildInteractor($this->workflowsWithFound(), $statuses, $transitions)
            ->update(
                $this->workflowId,
                $this->transitionId,
                TransitionName::fromString('start'),
                $this->from,
                $this->to,
            );
    }

    public function testUpdateThrowsWhenNewNameAlreadyExists(): void
    {
        $transitions = $this->createStub(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('findById')->willReturn($this->makeTransition());
        $transitions->method('existsByName')->willReturn(true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/already exists/');

        $this->buildInteractor(
            $this->workflowsWithFound(),
            $this->statusesWithBothFound(),
            $transitions,
        )->update(
            $this->workflowId,
            $this->transitionId,
            TransitionName::fromString('reopen'),
            $this->from,
            $this->to,
        );
    }
}
