<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Entity;

use App\TagFeature\Domain\Event\TagAssigned;
use App\TagFeature\Domain\Event\TagUnassigned;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TaggableType;

final class TagAssignment
{
    private string $id;
    private string $tagId;
    private string $entityType;
    private string $entityId;
    private string $assignedBy;
    private \DateTimeImmutable $assignedAt;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        string $id,
        TagId $tagId,
        TaggableType $entityType,
        string $entityId,
        string $assignedBy,
        \DateTimeImmutable $assignedAt,
    ) {
        $this->id = $id;
        $this->tagId = $tagId->value();
        $this->entityType = $entityType->value();
        $this->entityId = $entityId;
        $this->assignedBy = $assignedBy;
        $this->assignedAt = $assignedAt;
    }

    public static function create(
        string $id,
        TagId $tagId,
        TaggableType $entityType,
        string $entityId,
        string $assignedBy,
        \DateTimeImmutable $assignedAt,
    ): self {
        $assignment = new self($id, $tagId, $entityType, $entityId, $assignedBy, $assignedAt);
        $assignment->recordEvent(new TagAssigned($tagId, $entityType, $entityId));

        return $assignment;
    }

    public function markUnassigned(): void
    {
        $this->recordEvent(new TagUnassigned(
            TagId::fromString($this->tagId),
            TaggableType::fromString($this->entityType),
            $this->entityId,
        ));
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tagId(): TagId
    {
        return TagId::fromString($this->tagId);
    }

    public function entityType(): TaggableType
    {
        return TaggableType::fromString($this->entityType);
    }

    public function entityId(): string
    {
        return $this->entityId;
    }

    public function assignedBy(): string
    {
        return $this->assignedBy;
    }

    public function assignedAt(): \DateTimeImmutable
    {
        return $this->assignedAt;
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
