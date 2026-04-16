<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Repository;

use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\ValueObject\TeamId;

interface TeamMemberRepositoryInterface
{
    public function save(TeamMember $member): void;

    public function findByTeamId(TeamId $teamId): array;

    public function findByTeamAndUser(TeamId $teamId, string $userId): ?TeamMember;

    public function delete(TeamMember $member): void;
}
