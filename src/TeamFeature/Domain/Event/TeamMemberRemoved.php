<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Event;

use App\TeamFeature\Domain\ValueObject\TeamId;

final class TeamMemberRemoved
{
    public function __construct(
        public readonly TeamId $teamId,
        public readonly string $userId,
    ) {
    }
}
