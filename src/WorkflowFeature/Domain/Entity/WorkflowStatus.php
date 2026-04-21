<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Entity;

use App\WorkflowFeature\Domain\Event\WorkflowStatusAdded;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;

final class WorkflowStatus
{
    private string $id;
    private string $workflowId;
    private string $label;
    private bool $isInitial;
    private \DateTimeImmutable $createdAt;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        WorkflowStatusId $id,
        WorkflowId $workflowId,
        StatusLabel $label,
        bool $isInitial,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id->value();
        $this->workflowId = $workflowId->value();
        $this->label = $label->value();
        $this->isInitial = $isInitial;
        $this->createdAt = $createdAt;
    }

    public static function add(
        WorkflowStatusId $id,
        WorkflowId $workflowId,
        StatusLabel $label,
        bool $isInitial,
        \DateTimeImmutable $createdAt,
    ): self {
        $status = new self($id, $workflowId, $label, $isInitial, $createdAt);
        $status->recordEvent(new WorkflowStatusAdded($id, $workflowId, $label));

        return $status;
    }

    public function id(): WorkflowStatusId
    {
        return WorkflowStatusId::fromString($this->id);
    }

    public function workflowId(): WorkflowId
    {
        return WorkflowId::fromString($this->workflowId);
    }

    public function label(): StatusLabel
    {
        return StatusLabel::fromString($this->label);
    }

    public function isInitial(): bool
    {
        return $this->isInitial;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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
