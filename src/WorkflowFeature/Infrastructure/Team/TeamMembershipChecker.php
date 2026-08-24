<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Team;

use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\WorkflowFeature\Domain\Port\TeamMembershipInterface;

final class TeamMembershipChecker implements TeamMembershipInterface
{
    public function __construct(
        private readonly TeamMemberRepositoryInterface $teamMembers,
    ) {
    }

    public function isMember(string $teamId, string $userId): bool
    {
        return $this->teamMembers->findByTeamAndUser(TeamId::fromString($teamId), $userId) !== null;
    }
}
