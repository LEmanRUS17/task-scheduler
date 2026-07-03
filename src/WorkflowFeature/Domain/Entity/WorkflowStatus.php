<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Entity;

use App\DescriptionFeatureApi\Contract\DescribableInterface;
use App\WorkflowFeature\Domain\Event\WorkflowStatusAdded;
use App\WorkflowFeature\Domain\Event\WorkflowStatusUpdated;
use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowStatusId;

final class WorkflowStatus implements DescribableInterface
{
    private string $id;
    private string $workflowId;
    private string $label;
    private bool $isInitial;
    private bool $isFinal;
    private \DateTimeImmutable $createdAt;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        WorkflowStatusId $id,
        WorkflowId $workflowId,
        StatusLabel $label,
        bool $isInitial,
        bool $isFinal,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id->value();
        $this->workflowId = $workflowId->value();
        $this->label = $label->value();
        $this->isInitial = $isInitial;
        $this->isFinal = $isFinal;
        $this->createdAt = $createdAt;
    }

    public static function add(
        WorkflowStatusId $id,
        WorkflowId $workflowId,
        StatusLabel $label,
        bool $isInitial,
        \DateTimeImmutable $createdAt,
        bool $isFinal = false,
    ): self {
        $status = new self($id, $workflowId, $label, $isInitial, $isFinal, $createdAt);
        $status->recordEvent(new WorkflowStatusAdded($id, $workflowId, $label));

        return $status;
    }

    public function rename(StatusLabel $label): void
    {
        $this->label = $label->value();
        $this->recordEvent(new WorkflowStatusUpdated($this->id(), $this->workflowId(), $label));
    }

    public function markFinal(bool $isFinal): void
    {
        $this->isFinal = $isFinal;
        $this->recordEvent(new WorkflowStatusUpdated($this->id(), $this->workflowId(), $this->label()));
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

    public function isFinal(): bool
    {
        return $this->isFinal;
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
