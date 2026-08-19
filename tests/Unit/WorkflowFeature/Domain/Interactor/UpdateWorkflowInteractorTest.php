<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Exception\WorkflowAccessDeniedException;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowInteractor;
use App\WorkflowFeature\Domain\Port\DomainEventDispatcherInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;

final class UpdateWorkflowInteractorTest extends TestCase
{
    private WorkflowId $workflowId;

    protected function setUp(): void
    {
        $this->workflowId = WorkflowId::fromString('550e8400-e29b-4d4d-a716-446655440000');
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

    public function testUpdateSavesAndReturnsWorkflow(): void
    {
        $workflows = $this->createMock(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($this->makeWorkflow());
        $workflows->expects($this->once())->method('save');

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $workflow = (new UpdateWorkflowInteractor($workflows, $dispatcher))
            ->update($this->workflowId, 'user-1', WorkflowTitle::fromString('Renamed'));

        $this->assertSame('Renamed', $workflow->title()->value());
    }

    public function testUpdateThrowsWhenWorkflowNotFound(): void
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        (new UpdateWorkflowInteractor($workflows, $this->createStub(DomainEventDispatcherInterface::class)))
            ->update($this->workflowId, 'user-1', WorkflowTitle::fromString('Renamed'));
    }

    public function testUpdateThrowsWhenCallerIsNotTheOwner(): void
    {
        $workflows = $this->createMock(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($this->makeWorkflow());
        $workflows->expects($this->never())->method('save');

        $this->expectException(WorkflowAccessDeniedException::class);

        (new UpdateWorkflowInteractor($workflows, $this->createStub(DomainEventDispatcherInterface::class)))
            ->update($this->workflowId, 'user-2', WorkflowTitle::fromString('Renamed'));
    }
}
