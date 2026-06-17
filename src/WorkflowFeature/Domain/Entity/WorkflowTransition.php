<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Entity;

use App\DescriptionFeatureApi\Contract\DescribableInterface;
use App\WorkflowFeature\Domain\Event\WorkflowTransitionAdded;
use App\WorkflowFeature\Domain\Event\WorkflowTransitionUpdated;
use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTransitionId;

final class WorkflowTransition implements DescribableInterface
{
    private string $id;
    private string $workflowId;
    private string $name;
    private string $fromStatusId;
    private string $toStatusId;
    private \DateTimeImmutable $createdAt;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        WorkflowTransitionId $id,
        WorkflowId $workflowId,
        TransitionName $name,
        WorkflowStatusId $fromStatusId,
        WorkflowStatusId $toStatusId,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id->value();
        $this->workflowId = $workflowId->value();
        $this->name = $name->value();
        $this->fromStatusId = $fromStatusId->value();
        $this->toStatusId = $toStatusId->value();
        $this->createdAt = $createdAt;
    }

    public static function add(
        WorkflowTransitionId $id,
        WorkflowId $workflowId,
        TransitionName $name,
        WorkflowStatusId $fromStatusId,
        WorkflowStatusId $toStatusId,
        \DateTimeImmutable $createdAt,
    ): self {
        $transition = new self($id, $workflowId, $name, $fromStatusId, $toStatusId, $createdAt);
        $transition->recordEvent(new WorkflowTransitionAdded($id, $workflowId, $name));

        return $transition;
    }

    public function update(
        TransitionName $name,
        WorkflowStatusId $fromStatusId,
        WorkflowStatusId $toStatusId,
    ): void {
        $this->name = $name->value();
        $this->fromStatusId = $fromStatusId->value();
        $this->toStatusId = $toStatusId->value();
        $this->recordEvent(new WorkflowTransitionUpdated($this->id(), $this->workflowId(), $name));
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

    public function fromStatusId(): WorkflowStatusId
    {
        return WorkflowStatusId::fromString($this->fromStatusId);
    }

    public function toStatusId(): WorkflowStatusId
    {
        return WorkflowStatusId::fromString($this->toStatusId);
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
