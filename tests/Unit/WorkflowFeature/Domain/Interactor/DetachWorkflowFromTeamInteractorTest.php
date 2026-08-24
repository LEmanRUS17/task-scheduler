<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowTeam;
use App\WorkflowFeature\Domain\Interactor\DetachWorkflowFromTeamInteractor;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTeamRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;

final class DetachWorkflowFromTeamInteractorTest extends TestCase
{
    public function testDetachesWorkflowOwnedByCaller(): void
    {
        $workflow = $this->makeWorkflow('user-1');
        $link = WorkflowTeam::attach($workflow->id(), 'team-1', new \DateTimeImmutable('2025-01-01'));

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($workflow);

        $links = $this->createMock(WorkflowTeamRepositoryInterface::class);
        $links->method('findByWorkflowIdAndTeamId')->willReturn($link);
        $links->expects($this->once())->method('delete')->with($link);

        (new DetachWorkflowFromTeamInteractor($workflows, $links))
            ->detach($workflow->id(), 'team-1', 'user-1');
    }

    public function testRejectsDetachingWorkflowNotOwnedByCaller(): void
    {
        $workflow = $this->makeWorkflow('user-1');

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($workflow);

        $links = $this->createMock(WorkflowTeamRepositoryInterface::class);
        $links->expects($this->never())->method('delete');

        $this->expectException(\DomainException::class);

        (new DetachWorkflowFromTeamInteractor($workflows, $links))
            ->detach($workflow->id(), 'team-1', 'user-2');
    }

    public function testRejectsDetachingWorkflowThatIsNotAttached(): void
    {
        $workflow = $this->makeWorkflow('user-1');

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($workflow);

        $links = $this->createStub(WorkflowTeamRepositoryInterface::class);
        $links->method('findByWorkflowIdAndTeamId')->willReturn(null);

        $this->expectException(\DomainException::class);

        (new DetachWorkflowFromTeamInteractor($workflows, $links))
            ->detach($workflow->id(), 'team-1', 'user-1');
    }

    private function makeWorkflow(string $createdBy): Workflow
    {
        return Workflow::create(
            WorkflowId::generate(),
            WorkflowTitle::fromString('Bug flow'),
            $createdBy,
            new \DateTimeImmutable('2024-01-01 00:00:00'),
        );
    }
}
