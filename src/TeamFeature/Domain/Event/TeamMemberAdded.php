<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Event;

use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;

final class TeamMemberAdded
{
    public function __construct(
        public readonly TeamId $teamId,
        public readonly string $userId,
        public readonly TeamMemberRole $role,
    ) {}
}
