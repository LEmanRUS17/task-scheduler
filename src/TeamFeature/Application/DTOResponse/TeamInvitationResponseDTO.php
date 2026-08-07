<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DTOResponse;

use App\TeamFeatureApi\DTOResponse\TeamInvitationDataResponseInterface;

final class TeamInvitationResponseDTO implements TeamInvitationDataResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $teamId,
        private readonly string $invitedUserId,
        private readonly string $invitedByUserId,
        private readonly string $role,
        private readonly string $status,
        private readonly \DateTimeImmutable $createdAt,
        private readonly \DateTimeImmutable $expiresAt,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTeamId(): string
    {
        return $this->teamId;
    }

    public function getInvitedUserId(): string
    {
        return $this->invitedUserId;
    }

    public function getInvitedByUserId(): string
    {
        return $this->invitedByUserId;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
