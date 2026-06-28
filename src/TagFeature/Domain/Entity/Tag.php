<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Entity;

use App\DescriptionFeatureApi\Contract\DescribableInterface;
use App\TagFeature\Domain\Event\TagCreated;
use App\TagFeature\Domain\Event\TagDeleted;
use App\TagFeature\Domain\Event\TagUpdated;
use App\TagFeature\Domain\ValueObject\TagColor;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TagName;

final class Tag implements DescribableInterface
{
    private string $id;
    private string $ownerId;
    private string $name;
    private string $color;
    private \DateTimeImmutable $createdAt;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        TagId $id,
        string $ownerId,
        TagName $name,
        TagColor $color,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id->value();
        $this->ownerId = $ownerId;
        $this->name = $name->value();
        $this->color = $color->value();
        $this->createdAt = $createdAt;
    }

    public static function create(
        TagId $id,
        string $ownerId,
        TagName $name,
        TagColor $color,
        \DateTimeImmutable $createdAt,
    ): self {
        $tag = new self($id, $ownerId, $name, $color, $createdAt);
        $tag->recordEvent(new TagCreated($id, $ownerId, $name, $color));

        return $tag;
    }

    public function update(TagName $name, TagColor $color): void
    {
        $this->name = $name->value();
        $this->color = $color->value();
        $this->recordEvent(new TagUpdated($this->id(), $name, $color));
    }

    public function markDeleted(): void
    {
        $this->recordEvent(new TagDeleted($this->id()));
    }

    public function id(): TagId
    {
        return TagId::fromString($this->id);
    }

    public function ownerId(): string
    {
        return $this->ownerId;
    }

    public function name(): TagName
    {
        return TagName::fromString($this->name);
    }

    public function color(): TagColor
    {
        return TagColor::fromString($this->color);
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
