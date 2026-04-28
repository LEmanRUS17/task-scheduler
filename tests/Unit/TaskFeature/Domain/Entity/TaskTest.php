<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\Entity;

use App\TaskFeature\Domain\Entity\Task;
use App\TaskFeature\Domain\Event\TaskCreated;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    private TaskId $id;
    private TaskTitle $title;
    private \DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        $this->id = TaskId::generate();
        $this->title = TaskTitle::fromString('Test Task');
        $this->createdAt = new \DateTimeImmutable('2024-01-01 12:00:00');
    }

    private function makeTask(
        ?TaskTitle $title = null,
        ?string $teamId = null,
        ?\DateTimeImmutable $scheduledStart = null,
        ?\DateTimeImmutable $scheduledEnd = null,
        ?int $estimatedTime = null,
    ): Task {
        return Task::create(
            $this->id,
            $title ?? $this->title,
            TaskPriority::NORMAL,
            'default',
            $teamId,
            'user-1',
            $this->createdAt,
            $scheduledStart,
            $scheduledEnd,
            $estimatedTime,
        );
    }

    public function testCreateRecordsTaskCreatedEvent(): void
    {
        $task = $this->makeTask();
        $events = $task->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskCreated::class, $events[0]);
        $this->assertSame($this->id->value(), $events[0]->id->value());
        $this->assertSame('Test Task', $events[0]->title->value());
        $this->assertSame('user-1', $events[0]->createdBy);
    }

    public function testPullDomainEventsClearsQueue(): void
    {
        $task = $this->makeTask();
        $task->pullDomainEvents();

        $this->assertEmpty($task->pullDomainEvents());
    }

    public function testCreateStoresFields(): void
    {
        $start = new \DateTimeImmutable('2024-02-01');
        $end = new \DateTimeImmutable('2024-02-10');
        $task = $this->makeTask(scheduledStart: $start, scheduledEnd: $end, estimatedTime: 120, teamId: 'team-1');

        $this->assertSame($this->id->value(), $task->id()->value());
        $this->assertSame('Test Task', $task->title()->value());
        $this->assertSame(TaskPriority::NORMAL, $task->priority());
        $this->assertSame('team-1', $task->teamId());
        $this->assertSame('user-1', $task->createdBy());
        $this->assertSame($this->createdAt, $task->createdAt());
        $this->assertSame($start, $task->scheduledStart());
        $this->assertSame($end, $task->scheduledEnd());
        $this->assertSame(120, $task->estimatedTime());
        $this->assertNull($task->actualTime());
        $this->assertSame('', $task->getWorkflowStatus());
    }

    public function testUpdateChangesOnlyProvidedFields(): void
    {
        $task = $this->makeTask(estimatedTime: 60);
        $task->pullDomainEvents();

        $newTitle = TaskTitle::fromString('Updated');
        $task->update($newTitle, null, null, null, null);

        $this->assertSame('Updated', $task->title()->value());
        $this->assertSame(TaskPriority::NORMAL, $task->priority());
        $this->assertSame(60, $task->estimatedTime());
    }

    public function testUpdateAllFields(): void
    {
        $task = $this->makeTask();
        $task->pullDomainEvents();

        $newStart = new \DateTimeImmutable('2024-03-01');
        $newEnd = new \DateTimeImmutable('2024-03-15');
        $task->update(TaskTitle::fromString('New'), TaskPriority::HIGH, $newStart, $newEnd, 90);

        $this->assertSame('New', $task->title()->value());
        $this->assertSame(TaskPriority::HIGH, $task->priority());
        $this->assertSame($newStart, $task->scheduledStart());
        $this->assertSame($newEnd, $task->scheduledEnd());
        $this->assertSame(90, $task->estimatedTime());
    }

    public function testSetAndGetWorkflowStatus(): void
    {
        $task = $this->makeTask();
        $task->setWorkflowStatus('in_progress');

        $this->assertSame('in_progress', $task->getWorkflowStatus());
    }

    public function testLogActualTime(): void
    {
        $task = $this->makeTask();
        $task->logActualTime(45);

        $this->assertSame(45, $task->actualTime());
    }

    public function testGetWorkflowDefinitionTitle(): void
    {
        $task = $this->makeTask();

        $this->assertSame('default', $task->getWorkflowDefinitionTitle());
    }

    public function testNullableFieldsDefaultToNull(): void
    {
        $task = $this->makeTask();

        $this->assertNull($task->teamId());
        $this->assertNull($task->scheduledStart());
        $this->assertNull($task->scheduledEnd());
        $this->assertNull($task->estimatedTime());
        $this->assertNull($task->actualTime());
    }
}
