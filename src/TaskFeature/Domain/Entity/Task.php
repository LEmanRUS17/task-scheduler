<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Entity;

use App\TaskFeature\Domain\Event\TaskCreated;
use App\TaskFeature\Domain\ValueObject\TaskId;
use App\TaskFeature\Domain\ValueObject\TaskPriority;
use App\TaskFeature\Domain\ValueObject\TaskTitle;
use App\WorkflowFeatureApi\Contract\WorkflowSubjectInterface;

final class Task implements WorkflowSubjectInterface
{
    private string $id;
    private string $title;
    private TaskPriority $priority;
    private string $workflowStatus = '';
    private string $workflowDefinitionTitle;
    private string $createdBy;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $scheduledStart;
    private ?\DateTimeImmutable $scheduledEnd;
    private ?int $estimatedTime;
    private ?int $actualTime;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        TaskId $id,
        TaskTitle $title,
        TaskPriority $priority,
        string $workflowDefinitionTitle,
        string $createdBy,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $scheduledStart,
        ?\DateTimeImmutable $scheduledEnd,
        ?int $estimatedTime,
    ) {
        $this->id = $id->value();
        $this->title = $title->value();
        $this->priority = $priority;
        $this->workflowDefinitionTitle = $workflowDefinitionTitle;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->scheduledStart = $scheduledStart;
        $this->scheduledEnd = $scheduledEnd;
        $this->estimatedTime = $estimatedTime;
        $this->actualTime = null;
    }

    public static function create(
        TaskId $id,
        TaskTitle $title,
        TaskPriority $priority,
        string $workflowDefinitionTitle,
        string $createdBy,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $scheduledStart = null,
        ?\DateTimeImmutable $scheduledEnd = null,
        ?int $estimatedTime = null,
    ): self {
        $task = new self(
            $id,
            $title,
            $priority,
            $workflowDefinitionTitle,
            $createdBy,
            $createdAt,
            $scheduledStart,
            $scheduledEnd,
            $estimatedTime,
        );

        $task->recordEvent(new TaskCreated($id, $title, $createdBy));

        return $task;
    }

    public function id(): TaskId
    {
        return TaskId::fromString($this->id);
    }

    public function title(): TaskTitle
    {
        return TaskTitle::fromString($this->title);
    }

    public function priority(): TaskPriority
    {
        return $this->priority;
    }

    public function createdBy(): string
    {
        return $this->createdBy;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function scheduledStart(): ?\DateTimeImmutable
    {
        return $this->scheduledStart;
    }

    public function scheduledEnd(): ?\DateTimeImmutable
    {
        return $this->scheduledEnd;
    }

    public function estimatedTime(): ?int
    {
        return $this->estimatedTime;
    }

    public function actualTime(): ?int
    {
        return $this->actualTime;
    }

    public function logActualTime(int $minutes): void
    {
        $this->actualTime = $minutes;
    }

    public function getWorkflowStatus(): string
    {
        return $this->workflowStatus;
    }

    public function setWorkflowStatus(string $status): void
    {
        $this->workflowStatus = $status;
    }

    public function getWorkflowDefinitionTitle(): string
    {
        return $this->workflowDefinitionTitle;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<object> */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
