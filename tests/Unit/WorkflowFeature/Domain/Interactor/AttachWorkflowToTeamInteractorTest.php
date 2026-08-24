<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Interactor;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Entity\WorkflowTeam;
use App\WorkflowFeature\Domain\Interactor\AttachWorkflowToTeamInteractor;
use App\WorkflowFeature\Domain\Port\ClockInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTeamRepositoryInterface;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;

final class AttachWorkflowToTeamInteractorTest extends TestCase
{
    public function testAttachesWorkflowOwnedByCaller(): void
    {
        $workflow = $this->makeWorkflow('user-1');

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($workflow);

        $links = $this->createMock(WorkflowTeamRepositoryInterface::class);
        $links->method('findByWorkflowIdAndTeamId')->willReturn(null);
        $links->expects($this->once())->method('save');

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-01 12:00:00'));

        $link = (new AttachWorkflowToTeamInteractor($workflows, $links, $clock))
            ->attach($workflow->id(), 'team-1', 'user-1');

        $this->assertSame('team-1', $link->teamId());
        $this->assertSame($workflow->id()->value(), $link->workflowId()->value());
    }

    public function testIsIdempotentWhenAlreadyAttached(): void
    {
        $workflow = $this->makeWorkflow('user-1');
        $existing = WorkflowTeam::attach($workflow->id(), 'team-1', new \DateTimeImmutable('2025-01-01'));

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($workflow);

        $links = $this->createMock(WorkflowTeamRepositoryInterface::class);
        $links->method('findByWorkflowIdAndTeamId')->willReturn($existing);
        $links->expects($this->never())->method('save');

        $clock = $this->createStub(ClockInterface::class);

        $link = (new AttachWorkflowToTeamInteractor($workflows, $links, $clock))
            ->attach($workflow->id(), 'team-1', 'user-1');

        $this->assertSame($existing, $link);
    }

    public function testRejectsAttachingWorkflowNotOwnedByCaller(): void
    {
        $workflow = $this->makeWorkflow('user-1');

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($workflow);

        $links = $this->createMock(WorkflowTeamRepositoryInterface::class);
        $links->expects($this->never())->method('save');

        $this->expectException(\DomainException::class);

        (new AttachWorkflowToTeamInteractor($workflows, $links, $this->createStub(ClockInterface::class)))
            ->attach($workflow->id(), 'team-1', 'user-2');
    }

    public function testRejectsAttachingDefaultWorkflow(): void
    {
        $workflow = $this->makeWorkflow('user-1', isDefault: true);

        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn($workflow);

        $links = $this->createMock(WorkflowTeamRepositoryInterface::class);
        $links->expects($this->never())->method('save');

        $this->expectException(\DomainException::class);

        (new AttachWorkflowToTeamInteractor($workflows, $links, $this->createStub(ClockInterface::class)))
            ->attach($workflow->id(), 'team-1', 'user-1');
    }

    public function testRejectsAttachingUnknownWorkflow(): void
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        $workflows->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);

        (new AttachWorkflowToTeamInteractor(
            $workflows,
            $this->createStub(WorkflowTeamRepositoryInterface::class),
            $this->createStub(ClockInterface::class),
        ))->attach(WorkflowId::generate(), 'team-1', 'user-1');
    }

    private function makeWorkflow(string $createdBy, bool $isDefault = false): Workflow
    {
        return Workflow::create(
            WorkflowId::generate(),
            WorkflowTitle::fromString('Bug flow'),
            $createdBy,
            new \DateTimeImmutable('2024-01-01 00:00:00'),
            $isDefault,
        );
    }
}
