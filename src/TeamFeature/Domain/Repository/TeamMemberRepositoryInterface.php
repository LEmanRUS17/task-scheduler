<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Repository;

use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;

interface TeamMemberRepositoryInterface
{
    public function save(TeamMember $member): void;

    /** @return list<TeamMember> */
    public function findByTeamId(TeamId $teamId): array;

    /** @return list<TeamMember> */
    public function findByTeamIdAndRole(TeamId $teamId, TeamMemberRole $role): array;

    /** @return list<TeamMember> */
    public function findByUserId(string $userId): array;

    public function findByTeamAndUser(TeamId $teamId, string $userId): ?TeamMember;

    public function findByTeamAndUserAndRole(TeamId $teamId, string $userId, TeamMemberRole $role): ?TeamMember;

    public function delete(TeamMember $member): void;
}
