<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DTOResponse;

use App\TeamFeatureApi\DTOResponse\TeamMemberDataResponseInterface;

final class TeamMemberResponseDTO implements TeamMemberDataResponseInterface
{
    public function __construct(
        private readonly string $teamId,
        private readonly string $userId,
        private readonly string $role,
        private readonly \DateTimeImmutable $joinedAt,
    ) {}

    public function getTeamId(): string
    {
        return $this->teamId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
