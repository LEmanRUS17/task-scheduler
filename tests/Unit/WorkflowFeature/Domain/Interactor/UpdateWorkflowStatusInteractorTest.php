<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Exception\WorkflowAccessDeniedException;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowStatusInteractor;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;

final class UpdateWorkflowStatusInteractorTest extends TestCase
{
    private WorkflowId $workflowId;
    private WorkflowStatusId $statusId;

    protected function setUp(): void
    {
        $this->workflowId = WorkflowId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $this->statusId = WorkflowStatusId::fromString('550e8400-e29b-4d4d-a716-446655440001');
    }

    private function buildInteractor(
        WorkflowRepositoryInterface $workflows,
        WorkflowStatusRepositoryInterface $statuses,
        ?DomainEventDispatcherInterface $dispatcher = null,
    ): UpdateWorkflowStatusInteractor {
        return new UpdateWorkflowStatusInteractor(
            $workflows,
            $statuses,
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

    private function makeStatus(string $label = 'open'): WorkflowStatus
    {
        return WorkflowStatus::add(
            $this->statusId,
            $this->workflowId,
            StatusLabel::fromString($label),
            false,
            new \DateTimeImmutable(),
        );
    }

    public function testUpdateRenamesStatus(): void
    {
        $statuses = $this->createMock(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus('open'));
        $statuses->method('findByLabel')->willReturn(null);
        $statuses->expects($this->once())->method('save');

        $result = $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->update($this->workflowId, 'user-1', $this->statusId, StatusLabel::fromString('closed'));

        $this->assertSame('closed', $result->label()->value());
    }

    public function testUpdateDispatchesEvent(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus('open'));
        $statuses->method('findByLabel')->willReturn(null);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $this->buildInteractor($this->workflowsWithFound(), $statuses, $dispatcher)
            ->update($this->workflowId, 'user-1', $this->statusId, StatusLabel::fromString('closed'));
    }

    public function testUpdateAllowsKeepingSameLabel(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus('open'));

        $result = $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->update($this->workflowId, 'user-1', $this->statusId, StatusLabel::fromString('open'));

        $this->assertSame('open', $result->label()->value());
    }

    public function testUpdateMarksStatusFinalWhenRequested(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus('open'));
        $statuses->method('findByLabel')->willReturn(null);

        $result = $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->update($this->workflowId, 'user-1', $this->statusId, StatusLabel::fromString('open'), true);

        $this->assertTrue($result->isFinal());
    }

    public function testUpdateLeavesFinalFlagUnchangedWhenNotProvided(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus('open'));
        $statuses->method('findByLabel')->willReturn(null);

        $result = $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->update($this->workflowId, 'user-1', $this->statusId, StatusLabel::fromString('open'));

        $this->assertFalse($result->isFinal());
    }

    public function testUpdateThrowsWhenWorkflowNotFound(): void
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->buildInteractor($workflows, $this->createStub(WorkflowStatusRepositoryInterface::class))
            ->update($this->workflowId, 'user-1', $this->statusId, StatusLabel::fromString('closed'));
    }

    public function testUpdateThrowsWhenCallerIsNotTheOwner(): void
    {
        $statuses = $this->createMock(WorkflowStatusRepositoryInterface::class);
        $statuses->expects($this->never())->method('save');

        $this->expectException(WorkflowAccessDeniedException::class);

        $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->update($this->workflowId, 'user-2', $this->statusId, StatusLabel::fromString('closed'));
    }

    public function testUpdateThrowsWhenStatusNotFound(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->update($this->workflowId, 'user-1', $this->statusId, StatusLabel::fromString('closed'));
    }

    public function testUpdateThrowsWhenNewLabelAlreadyExists(): void
    {
        $other = WorkflowStatus::add(
            WorkflowStatusId::fromString('550e8400-e29b-4d4d-a716-4466554400ff'),
            $this->workflowId,
            StatusLabel::fromString('closed'),
            false,
            new \DateTimeImmutable(),
        );

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findById')->willReturn($this->makeStatus('open'));
        $statuses->method('findByLabel')->willReturn($other);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/already exists/');

        $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->update($this->workflowId, 'user-1', $this->statusId, StatusLabel::fromString('closed'));
    }
}
