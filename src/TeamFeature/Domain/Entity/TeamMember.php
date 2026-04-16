<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Entity;

use App\TeamFeature\Domain\Event\TeamMemberAdded;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;

final class TeamMember
{
    private string $teamId;
    private string $userId;
    private TeamMemberRole $role;
    private \DateTimeImmutable $joinedAt;

    private array $domainEvents = [];

    private function __construct(
        TeamId $teamId,
        string $userId,
        TeamMemberRole $role,
        \DateTimeImmutable $joinedAt,
    ) {
        $this->teamId = $teamId->value();
        $this->userId = $userId;
        $this->role = $role;
        $this->joinedAt = $joinedAt;
    }

    public static function add(
        TeamId $teamId,
        string $userId,
        TeamMemberRole $role,
        \DateTimeImmutable $joinedAt,
    ): self {
        $member = new self($teamId, $userId, $role, $joinedAt);
        $member->recordEvent(new TeamMemberAdded($teamId, $userId, $role));

        return $member;
    }

    public function teamId(): TeamId
    {
        return TeamId::fromString($this->teamId);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function role(): TeamMemberRole
    {
        return $this->role;
    }

    public function joinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
