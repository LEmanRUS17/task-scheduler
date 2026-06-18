<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Event;

final class TeamUpdated
{
    public function __construct(
        public readonly string $teamId,
    ) {
    }
}
