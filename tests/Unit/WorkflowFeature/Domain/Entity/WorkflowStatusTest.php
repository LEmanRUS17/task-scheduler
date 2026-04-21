<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Entity;

use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Event\WorkflowStatusAdded;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use PHPUnit\Framework\TestCase;

final class WorkflowStatusTest extends TestCase
{
    private WorkflowStatusId $id;
    private WorkflowId $workflowId;
    private StatusLabel $label;
    private \DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        $this->id = WorkflowStatusId::fromString('550e8400-e29b-4d4d-a716-446655440001');
        $this->workflowId = WorkflowId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $this->label = StatusLabel::fromString('open');
        $this->createdAt = new \DateTimeImmutable('2024-01-01 12:00:00');
    }

    public function testAddReturnsStatusWithCorrectData(): void
    {
        $status = WorkflowStatus::add($this->id, $this->workflowId, $this->label, true, $this->createdAt);

        $this->assertSame($this->id->value(), $status->id()->value());
        $this->assertSame($this->workflowId->value(), $status->workflowId()->value());
        $this->assertSame('open', $status->label()->value());
        $this->assertTrue($status->isInitial());
        $this->assertEquals($this->createdAt, $status->createdAt());
    }

    public function testAddNonInitialStatus(): void
    {
        $status = WorkflowStatus::add($this->id, $this->workflowId, $this->label, false, $this->createdAt);

        $this->assertFalse($status->isInitial());
    }

    public function testAddRecordsWorkflowStatusAddedEvent(): void
    {
        $status = WorkflowStatus::add($this->id, $this->workflowId, $this->label, true, $this->createdAt);

        $events = $status->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(WorkflowStatusAdded::class, $events[0]);
        $this->assertSame($this->id->value(), $events[0]->id->value());
        $this->assertSame($this->workflowId->value(), $events[0]->workflowId->value());
        $this->assertSame('open', $events[0]->label->value());
    }

    public function testPullDomainEventsClearsEvents(): void
    {
        $status = WorkflowStatus::add($this->id, $this->workflowId, $this->label, true, $this->createdAt);

        $status->pullDomainEvents();

        $this->assertEmpty($status->pullDomainEvents());
    }
}
