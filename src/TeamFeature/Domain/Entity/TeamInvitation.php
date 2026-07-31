<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Entity;

use App\AuditLogFeatureApi\Contract\AuditableInterface;
use App\TeamFeature\Domain\Event\TeamMemberInvited;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamInvitationId;
use App\TeamFeature\Domain\ValueObject\TeamInvitationStatus;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;

final class TeamInvitation implements AuditableInterface
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
    private ?\DateTimeImmutable $respondedAt = null;

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

    /**
     * Marks the invitation accepted after validating the token matches and the
     * invitation is still pending and not expired.
     *
     * @throws \DomainException when already accepted, the token does not match,
     *                          or the invitation has expired.
     */
    public function accept(string $token, \DateTimeImmutable $now): void
    {
        if ($this->status === TeamInvitationStatus::ACCEPTED) {
            throw new \DomainException('Invitation has already been accepted');
        }

        if (!hash_equals($this->token, $token)) {
            throw new \DomainException('Invalid invitation token');
        }

        if ($this->expiresAt < $now) {
            throw new \DomainException('Invitation has expired');
        }

        $this->status = TeamInvitationStatus::ACCEPTED;
        $this->respondedAt = $now;
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

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function respondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
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
