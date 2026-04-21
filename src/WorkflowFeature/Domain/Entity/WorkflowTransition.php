<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Entity;

use App\WorkflowFeature\Domain\Event\WorkflowTransitionAdded;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;

final class WorkflowTransition
{
    private string $id;
    private string $workflowId;
    private string $name;
    private string $fromStatusLabel;
    private string $toStatusLabel;
    private \DateTimeImmutable $createdAt;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        WorkflowTransitionId $id,
        WorkflowId $workflowId,
        TransitionName $name,
        StatusLabel $fromStatusLabel,
        StatusLabel $toStatusLabel,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id->value();
        $this->workflowId = $workflowId->value();
        $this->name = $name->value();
        $this->fromStatusLabel = $fromStatusLabel->value();
        $this->toStatusLabel = $toStatusLabel->value();
        $this->createdAt = $createdAt;
    }

    public static function add(
        WorkflowTransitionId $id,
        WorkflowId $workflowId,
        TransitionName $name,
        StatusLabel $fromStatusLabel,
        StatusLabel $toStatusLabel,
        \DateTimeImmutable $createdAt,
    ): self {
        $transition = new self($id, $workflowId, $name, $fromStatusLabel, $toStatusLabel, $createdAt);
        $transition->recordEvent(new WorkflowTransitionAdded($id, $workflowId, $name));

        return $transition;
    }

    public function id(): WorkflowTransitionId
    {
        return WorkflowTransitionId::fromString($this->id);
    }

    public function workflowId(): WorkflowId
    {
        return WorkflowId::fromString($this->workflowId);
    }

    public function name(): TransitionName
    {
        return TransitionName::fromString($this->name);
    }

    public function fromStatusLabel(): StatusLabel
    {
        return StatusLabel::fromString($this->fromStatusLabel);
    }

    public function toStatusLabel(): StatusLabel
    {
        return StatusLabel::fromString($this->toStatusLabel);
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
