<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Entity;

use App\TeamFeature\Domain\Event\TeamMemberInvited;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamInvitationId;
use App\TeamFeature\Domain\ValueObject\TeamInvitationStatus;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;

final class TeamInvitation
{
    private string $id;
    private string $teamId;
    private string $invitedUserId;
    private string $invitedByUserId;
    private TeamMemberRole $role;
    private TeamInvitationStatus $status;
    private string $token;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $expiresAt;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        TeamInvitationId $id,
        TeamId $teamId,
        string $invitedUserId,
        string $invitedByUserId,
        TeamMemberRole $role,
        string $token,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $expiresAt,
    ) {
        $this->id = $id->value();
        $this->teamId = $teamId->value();
        $this->invitedUserId = $invitedUserId;
        $this->invitedByUserId = $invitedByUserId;
        $this->role = $role;
        $this->status = TeamInvitationStatus::PENDING;
        $this->token = $token;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }

    public static function create(
        TeamInvitationId $id,
        TeamId $teamId,
        string $teamTitle,
        string $invitedUserId,
        string $invitedEmail,
        string $invitedByUserId,
        TeamMemberRole $role,
        string $token,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $expiresAt,
    ): self {
        $invitation = new self($id, $teamId, $invitedUserId, $invitedByUserId, $role, $token, $createdAt, $expiresAt);
        $invitation->recordEvent(new TeamMemberInvited(
            $teamId,
            $teamTitle,
            $id->value(),
            $invitedUserId,
            $invitedEmail,
            $invitedByUserId,
            $role,
            $token,
        ));

        return $invitation;
    }

    public function id(): TeamInvitationId
    {
        return TeamInvitationId::fromString($this->id);
    }

    public function teamId(): TeamId
    {
        return TeamId::fromString($this->teamId);
    }

    public function invitedUserId(): string
    {
        return $this->invitedUserId;
    }

    public function invitedByUserId(): string
    {
        return $this->invitedByUserId;
    }

    public function role(): TeamMemberRole
    {
        return $this->role;
    }

    public function status(): TeamInvitationStatus
    {
        return $this->status;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
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
