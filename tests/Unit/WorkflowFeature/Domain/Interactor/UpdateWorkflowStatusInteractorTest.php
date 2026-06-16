<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Interactor\UpdateWorkflowStatusInteractor;
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

    protected function setUp(): void
    {
        $this->workflowId = WorkflowId::fromString('550e8400-e29b-4d4d-a716-446655440000');
    }

    private function buildInteractor(
        WorkflowRepositoryInterface $workflows,
        WorkflowStatusRepositoryInterface $statuses,
    ): UpdateWorkflowStatusInteractor {
        return new UpdateWorkflowStatusInteractor($workflows, $statuses);
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

    public function testUpdateReturnsStatusWhenFound(): void
    {
        $status = WorkflowStatus::add(
            WorkflowStatusId::fromString('550e8400-e29b-4d4d-a716-446655440001'),
            $this->workflowId,
            StatusLabel::fromString('open'),
            false,
            new \DateTimeImmutable(),
        );

        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByLabel')->willReturn($status);

        $result = $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->update($this->workflowId, StatusLabel::fromString('open'));

        $this->assertSame('open', $result->label()->value());
    }

    public function testUpdateThrowsWhenWorkflowNotFound(): void
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->buildInteractor($workflows, $this->createStub(WorkflowStatusRepositoryInterface::class))
            ->update($this->workflowId, StatusLabel::fromString('open'));
    }

    public function testUpdateThrowsWhenStatusNotFound(): void
    {
        $statuses = $this->createStub(WorkflowStatusRepositoryInterface::class);
        $statuses->method('findByLabel')->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->buildInteractor($this->workflowsWithFound(), $statuses)
            ->update($this->workflowId, StatusLabel::fromString('open'));
    }
}
