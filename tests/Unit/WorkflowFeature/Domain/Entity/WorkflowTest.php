<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\Entity;

use App\WorkflowFeature\Domain\Entity\Workflow;
use App\WorkflowFeature\Domain\Event\WorkflowCreated;
use App\WorkflowFeature\Domain\Event\WorkflowUpdated;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;

final class WorkflowTest extends TestCase
{
    private WorkflowId $id;
    private WorkflowTitle $title;
    private \DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        $this->id = WorkflowId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $this->title = WorkflowTitle::fromString('Test Workflow');
        $this->createdAt = new \DateTimeImmutable('2024-01-01 12:00:00');
    }

    public function testCreateReturnsWorkflowWithCorrectData(): void
    {
        $workflow = Workflow::create($this->id, $this->title, 'user-1', $this->createdAt);

        $this->assertSame($this->id->value(), $workflow->id()->value());
        $this->assertSame('Test Workflow', $workflow->title()->value());
        $this->assertSame('user-1', $workflow->createdBy());
        $this->assertEquals($this->createdAt, $workflow->createdAt());
    }

    public function testCreateRecordsWorkflowCreatedEvent(): void
    {
        $workflow = Workflow::create($this->id, $this->title, 'user-1', $this->createdAt);

        $events = $workflow->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(WorkflowCreated::class, $events[0]);
        $this->assertSame($this->id->value(), $events[0]->id->value());
        $this->assertSame('Test Workflow', $events[0]->title->value());
        $this->assertSame('user-1', $events[0]->createdBy);
    }

    public function testUpdateTitleChangesTitle(): void
    {
        $workflow = Workflow::create($this->id, $this->title, 'user-1', $this->createdAt);
        $workflow->pullDomainEvents();

        $workflow->updateTitle(WorkflowTitle::fromString('Renamed Workflow'));

        $this->assertSame('Renamed Workflow', $workflow->title()->value());
    }

    public function testUpdateTitleRecordsWorkflowUpdatedEvent(): void
    {
        $workflow = Workflow::create($this->id, $this->title, 'user-1', $this->createdAt);
        $workflow->pullDomainEvents();

        $workflow->updateTitle(WorkflowTitle::fromString('Renamed Workflow'));

        $events = $workflow->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(WorkflowUpdated::class, $events[0]);
        $this->assertSame($this->id->value(), $events[0]->id->value());
        $this->assertSame('Renamed Workflow', $events[0]->title->value());
    }

    public function testPullDomainEventsClearsEvents(): void
    {
        $workflow = Workflow::create($this->id, $this->title, 'user-1', $this->createdAt);

        $workflow->pullDomainEvents();

        $this->assertEmpty($workflow->pullDomainEvents());
    }
}
