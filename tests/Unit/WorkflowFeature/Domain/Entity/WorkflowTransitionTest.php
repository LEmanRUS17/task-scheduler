<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Entity;

use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Event\WorkflowTransitionAdded;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;
use PHPUnit\Framework\TestCase;

final class WorkflowTransitionTest extends TestCase
{
    private WorkflowTransitionId $id;
    private WorkflowId $workflowId;
    private TransitionName $name;
    private StatusLabel $from;
    private StatusLabel $to;
    private \DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        $this->id = WorkflowTransitionId::fromString('550e8400-e29b-4d4d-a716-446655440002');
        $this->workflowId = WorkflowId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $this->name = TransitionName::fromString('start');
        $this->from = StatusLabel::fromString('open');
        $this->to = StatusLabel::fromString('in-progress');
        $this->createdAt = new \DateTimeImmutable('2024-01-01 12:00:00');
    }

    public function testAddReturnsTransitionWithCorrectData(): void
    {
        $transition = WorkflowTransition::add(
            $this->id,
            $this->workflowId,
            $this->name,
            $this->from,
            $this->to,
            $this->createdAt,
        );

        $this->assertSame($this->id->value(), $transition->id()->value());
        $this->assertSame($this->workflowId->value(), $transition->workflowId()->value());
        $this->assertSame('start', $transition->name()->value());
        $this->assertSame('open', $transition->fromStatusLabel()->value());
        $this->assertSame('in-progress', $transition->toStatusLabel()->value());
        $this->assertEquals($this->createdAt, $transition->createdAt());
    }

    public function testAddRecordsWorkflowTransitionAddedEvent(): void
    {
        $transition = WorkflowTransition::add(
            $this->id,
            $this->workflowId,
            $this->name,
            $this->from,
            $this->to,
            $this->createdAt,
        );

        $events = $transition->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(WorkflowTransitionAdded::class, $events[0]);
        $this->assertSame($this->id->value(), $events[0]->id->value());
        $this->assertSame($this->workflowId->value(), $events[0]->workflowId->value());
        $this->assertSame('start', $events[0]->name->value());
    }

    public function testPullDomainEventsClearsEvents(): void
    {
        $transition = WorkflowTransition::add(
            $this->id,
            $this->workflowId,
            $this->name,
            $this->from,
            $this->to,
            $this->createdAt,
        );

        $transition->pullDomainEvents();

        $this->assertEmpty($transition->pullDomainEvents());
    }
}
