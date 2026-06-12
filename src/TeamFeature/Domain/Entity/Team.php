<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Entity;

use App\DescriptionFeatureApi\Contract\DescribableInterface;
use App\TeamFeature\Domain\Event\TeamCreated;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamStatus;
use App\AuditLogFeatureApi\Contract\AuditableInterface;
use App\TeamFeature\Domain\ValueObject\Title;

final class Team implements AuditableInterface, DescribableInterface
{
    private string $id;
    private string $title;
    private \DateTimeImmutable $createdAt;
    private TeamStatus $status;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        TeamId $id,
        Title $title,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id->value();
        $this->title = $title->value();
        $this->status = TeamStatus::ACTIVE;
        $this->createdAt = $createdAt;
    }

    public static function create(
        TeamId $id,
        Title $title,
        \DateTimeImmutable $createdAt,
    ): self {
        $team = new self($id, $title, $createdAt);
        $team->recordEvent(new TeamCreated($id, $title));

        return $team;
    }

    public function id(): TeamId
    {
        return TeamId::fromString($this->id);
    }

    public function title(): Title
    {
        return Title::fromString($this->title);
    }

    public function status(): TeamStatus
    {
        return $this->status;
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
