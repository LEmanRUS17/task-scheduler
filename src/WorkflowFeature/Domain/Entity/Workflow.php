<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Entity;

use App\WorkflowFeature\Domain\Event\WorkflowCreated;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;

final class Workflow
{
    private string $id;
    private string $title;
    private string $createdBy;
    private \DateTimeImmutable $createdAt;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        WorkflowId $id,
        WorkflowTitle $title,
        string $createdBy,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id->value();
        $this->title = $title->value();
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
    }

    public static function create(
        WorkflowId $id,
        WorkflowTitle $title,
        string $createdBy,
        \DateTimeImmutable $createdAt,
    ): self {
        $workflow = new self($id, $title, $createdBy, $createdAt);
        $workflow->recordEvent(new WorkflowCreated($id, $title, $createdBy));

        return $workflow;
    }

    public function id(): WorkflowId
    {
        return WorkflowId::fromString($this->id);
    }

    public function title(): WorkflowTitle
    {
        return WorkflowTitle::fromString($this->title);
    }

    public function createdBy(): string
    {
        return $this->createdBy;
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
