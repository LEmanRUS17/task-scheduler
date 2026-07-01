<?php

declare(strict_types=1);

namespace App\TagFeature\Infrastructure\Team;

use App\TagFeature\Domain\Port\TeamMembershipInterface;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;

final class TagTeamMembershipChecker implements TeamMembershipInterface
{
    public function __construct(
        private readonly TeamMemberRepositoryInterface $teamMembers,
    ) {
    }

    public function isMember(string $teamId, string $userId): bool
    {
        return $this->teamMembers->findByTeamAndUser(TeamId::fromString($teamId), $userId) !== null;
    }

    /** @return list<string> */
    public function teamIdsOf(string $userId): array
    {
        return array_map(
            static fn($member) => $member->teamId()->value(),
            $this->teamMembers->findByUserId($userId),
        );
    }
}
